<?php

namespace App\Services\Reconciliation;

use App\Models\Trade;
use App\Services\WebSocket\RestBackfill;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ReconciliationService
{
    public function __construct(
        private RestBackfill $restBackfill
    ) {}

    public function reconcileOrphanPositions(): array
    {
        $orphansFound = [];
        $tpSlAttached = 0;

        try {
            // Get all positions from exchange
            $exchangePositions = $this->restBackfill->backfillPositions();

            foreach ($exchangePositions as $position) {
                $symbol = $position['symbol'];
                $size = (float) $position['size'];

                if ($size == 0) {
                    continue; // No position
                }

                // Check if we have this position in our DB
                $dbTrade = Trade::where('symbol', $symbol)
                    ->where('status', 'OPEN')
                    ->first();

                if (! $dbTrade) {
                    // Orphan position found!
                    $orphansFound[] = $symbol;

                    // Create trade record
                    $trade = Trade::create([
                        'symbol' => $symbol,
                        'side' => $position['side'] === 'Buy' ? 'LONG' : 'SHORT',
                        'qty' => abs($size),
                        'entry_price' => (float) $position['avgPrice'],
                        'status' => 'OPEN',
                        'margin_mode' => 'ISOLATED',
                        'leverage' => 1,
                        'opened_at' => now(),
                        'meta' => json_encode(['source' => 'RECONCILIATION_ORPHAN']),
                    ]);

                    // Attach TP/SL orders
                    if ($this->attachTpSlOrders($trade)) {
                        $tpSlAttached++;
                    }

                    Log::info('Orphan position reconciled', [
                        'symbol' => $symbol,
                        'size' => $size,
                        'trade_id' => $trade->id,
                    ]);
                }
            }

            return [
                'orphans_found' => count($orphansFound),
                'symbols' => $orphansFound,
                'tp_sl_attached' => $tpSlAttached,
            ];

        } catch (\Exception $e) {
            Log::error('Orphan reconciliation failed', ['error' => $e->getMessage()]);

            return ['orphans_found' => 0, 'tp_sl_attached' => 0, 'error' => $e->getMessage()];
        }
    }

    public function reconcileExternalCloses(): array
    {
        $externalCloses = [];

        try {
            // Get all open trades from DB
            $openTrades = Trade::where('status', 'OPEN')->get();

            foreach ($openTrades as $trade) {
                // Check if position still exists on exchange
                $exchangePositions = $this->restBackfill->backfillPositions($trade->symbol);

                $positionExists = collect($exchangePositions)->first(function ($pos) use ($trade) {
                    return $pos['symbol'] === $trade->symbol && (float) $pos['size'] != 0;
                });

                if (! $positionExists) {
                    // Position was closed externally
                    $externalCloses[] = $trade->symbol;

                    // Get recent executions to find close price
                    $executions = $this->restBackfill->backfillExecutions(
                        $trade->symbol,
                        now()->subHours(24),
                        now()
                    );

                    $closeExecution = collect($executions)->first(function ($exec) use ($trade) {
                        $execSide = $exec['side'];
                        $tradeSide = $trade->side;

                        // Closing execution has opposite side
                        return ($tradeSide === 'LONG' && $execSide === 'Sell') ||
                               ($tradeSide === 'SHORT' && $execSide === 'Buy');
                    });

                    if ($closeExecution) {
                        $closePrice = (float) $closeExecution['execPrice'];
                        $pnl = $this->calculatePnl($trade, $closePrice);

                        $trade->update([
                            'status' => 'CLOSED',
                            'pnl' => $pnl,
                            'closed_at' => now(),
                            'meta' => array_merge(
                                json_decode($trade->meta ?? '{}', true),
                                ['close_source' => 'EXTERNAL_RECONCILIATION']
                            ),
                        ]);

                        Log::info('External close reconciled', [
                            'symbol' => $trade->symbol,
                            'trade_id' => $trade->id,
                            'close_price' => $closePrice,
                            'pnl' => $pnl,
                        ]);
                    }
                }
            }

            return [
                'external_closes' => count($externalCloses),
                'symbols' => $externalCloses,
            ];

        } catch (\Exception $e) {
            Log::error('External close reconciliation failed', ['error' => $e->getMessage()]);

            return ['external_closes' => 0, 'error' => $e->getMessage()];
        }
    }

    private function attachTpSlOrders(Trade $trade): bool
    {
        try {
            $entryPrice = $trade->entry_price;
            $isLong = $trade->side === 'LONG';

            // Calculate TP/SL prices (2% profit, 1% loss)
            $tpPrice = $isLong ? $entryPrice * 1.02 : $entryPrice * 0.98;
            $slPrice = $isLong ? $entryPrice * 0.99 : $entryPrice * 1.01;

            // Create TP order
            $tpResponse = Http::post('https://api-testnet.bybit.com/v5/order/create', [
                'category' => 'linear',
                'symbol' => $trade->symbol,
                'side' => $isLong ? 'Sell' : 'Buy',
                'orderType' => 'Limit',
                'qty' => (string) $trade->qty,
                'price' => (string) $tpPrice,
                'timeInForce' => 'GTC',
                'reduceOnly' => true,
            ]);

            // Create SL order
            $slResponse = Http::post('https://api-testnet.bybit.com/v5/order/create', [
                'category' => 'linear',
                'symbol' => $trade->symbol,
                'side' => $isLong ? 'Sell' : 'Buy',
                'orderType' => 'Market',
                'qty' => (string) $trade->qty,
                'stopLoss' => (string) $slPrice,
                'timeInForce' => 'GTC',
                'reduceOnly' => true,
            ]);

            return $tpResponse->successful() && $slResponse->successful();

        } catch (\Exception $e) {
            Log::error('TP/SL attachment failed', [
                'trade_id' => $trade->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    private function calculatePnl(Trade $trade, float $closePrice): float
    {
        $entryPrice = $trade->entry_price;
        $qty = $trade->qty;
        $isLong = $trade->side === 'LONG';

        if ($isLong) {
            return ($closePrice - $entryPrice) * $qty;
        } else {
            return ($entryPrice - $closePrice) * $qty;
        }
    }
}
