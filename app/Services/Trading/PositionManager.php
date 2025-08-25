<?php

declare(strict_types=1);

namespace App\Services\Trading;

use App\Models\Trade;
use App\Services\AI\ConsensusService;
use App\Services\Exchange\BybitClient;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;

class PositionManager
{
    public function __construct(
        private ConsensusService $consensus,
        private BybitClient $bybit,
        private TradeCloser $closer,
    ) {}

    /**
     * @return array{action:string, qty?:float, closed?:bool}
     */
    public function manage(Trade $trade, array $snapshot): array
    {
        // Manage prompt için pozisyonu snapshot'a ekle
        $snapshot['position'] = [
            'symbol' => $trade->symbol,
            'side' => $trade->side,
            'qty' => (float) $trade->qty,
            'entry_price' => (float) $trade->entry_price,
            'stop_loss' => $trade->stop_loss !== null ? (float) $trade->stop_loss : null,
            'take_profit' => $trade->take_profit !== null ? (float) $trade->take_profit : null,
        ];

        $decisionPack = $this->consensus->decide($snapshot); // AiDecision final
        $final = $decisionPack['final'] ?? [];
        $action = strtoupper((string) Arr::get($final, 'action', 'HOLD'));
        $qtyDelta = (float) (Arr::get($final, 'qty_delta_factor') ?? 0.0);
        $newSL = Arr::get($final, 'stop_loss');
        $newTP = Arr::get($final, 'take_profit');

        // Lokal SL/TP güncelle (sunucu tarafı endpoint ileride)
        $updated = false;
        if ($newSL !== null) {
            $trade->stop_loss = (float) $newSL;
            $updated = true;
        }
        if ($newTP !== null) {
            $trade->take_profit = (float) $newTP;
            $updated = true;
        }
        if ($updated) {
            $trade->save();
        }

        if ($action === 'CLOSE') {
            $this->closer->closeMarket($trade->symbol, $trade->side, (float) $trade->qty);
            $trade->status = 'CLOSED';
            $trade->closed_at = Carbon::now();
            $trade->save();

            return ['action' => 'CLOSE', 'closed' => true];
        }

        if ($action === 'HOLD' && abs($qtyDelta) > 0.00001) {
            $deltaQty = (float) $trade->qty * $qtyDelta; // pozitif: ekle, negatif: azalt
            if ($deltaQty > 0) {
                // scale-in: aynı taraf MARKET IOC
                $this->bybit->createOrder(
                    $trade->symbol,
                    $trade->side,
                    'MARKET',
                    $deltaQty,
                    null,
                    ['timeInForce' => 'IOC']
                );
                $trade->qty = (float) $trade->qty + $deltaQty;
                $trade->save();

                return ['action' => 'SCALE_IN', 'qty' => $deltaQty];
            }
            if ($deltaQty < 0) {
                // scale-out: reduceOnly MARKET IOC
                $this->closer->closeMarket($trade->symbol, $trade->side, abs($deltaQty));
                $trade->qty = max(0.0, (float) $trade->qty + $deltaQty);
                if ($trade->qty == 0.0) {
                    $trade->status = 'CLOSED';
                    $trade->closed_at = Carbon::now();
                }
                $trade->save();

                return ['action' => 'SCALE_OUT', 'qty' => abs($deltaQty)];
            }
        }

        return ['action' => 'HOLD'];
    }

    /**
     * Gelişmiş SCALE-IN/OUT stratejisi
     *
     * @param  Trade  $trade  Pozisyon
     * @param  array  $snapshot  Market snapshot
     * @param  float  $scaleFactor  Scale faktörü (0.1 = %10, 0.25 = %25)
     * @param  string  $scaleType  'IN' veya 'OUT'
     * @return array ['action' => string, 'qty' => float, 'reason' => string]
     */
    public function scalePosition(
        Trade $trade,
        array $snapshot,
        float $scaleFactor,
        string $scaleType
    ): array {
        $scaleFactor = max(0.01, min(0.5, $scaleFactor)); // %1-%50 arası
        $scaleQty = (float) $trade->qty * $scaleFactor;

        if ($scaleType === 'IN') {
            // Scale-IN: Aynı taraf MARKET IOC
            try {
                $this->bybit->createOrder(
                    $trade->symbol,
                    $trade->side,
                    'MARKET',
                    $scaleQty,
                    null,
                    ['timeInForce' => 'IOC']
                );

                $trade->qty = (float) $trade->qty + $scaleQty;
                $trade->save();

                return [
                    'action' => 'SCALE_IN',
                    'qty' => $scaleQty,
                    'reason' => 'Manual scale-in executed',
                    'new_total_qty' => $trade->qty,
                ];
            } catch (\Throwable $e) {
                return [
                    'action' => 'SCALE_IN_FAILED',
                    'qty' => 0.0,
                    'reason' => 'Scale-in failed: '.$e->getMessage(),
                ];
            }
        }

        if ($scaleType === 'OUT') {
            // Scale-OUT: ReduceOnly MARKET IOC
            try {
                $this->closer->closeMarket($trade->symbol, $trade->side, $scaleQty);

                $trade->qty = max(0.0, (float) $trade->qty - $scaleQty);

                if ($trade->qty <= 0.001) {
                    $trade->status = 'CLOSED';
                    $trade->closed_at = Carbon::now();
                }

                $trade->save();

                return [
                    'action' => 'SCALE_OUT',
                    'qty' => $scaleQty,
                    'reason' => 'Manual scale-out executed',
                    'new_total_qty' => $trade->qty,
                    'closed' => $trade->status === 'CLOSED',
                ];
            } catch (\Throwable $e) {
                return [
                    'action' => 'SCALE_OUT_FAILED',
                    'qty' => 0.0,
                    'reason' => 'Scale-out failed: '.$e->getMessage(),
                ];
            }
        }

        return [
            'action' => 'INVALID_SCALE_TYPE',
            'qty' => 0.0,
            'reason' => 'Invalid scale type: '.$scaleType,
        ];
    }

    /**
     * Pozisyon risk analizi
     *
     * @param  Trade  $trade  Pozisyon
     * @param  array  $snapshot  Market snapshot
     * @return array ['risk_level' => string, 'details' => array, 'recommendations' => array]
     */
    public function analyzePositionRisk(Trade $trade, array $snapshot): array
    {
        $entryPrice = (float) $trade->entry_price;
        $currentPrice = (float) ($snapshot['price'] ?? $entryPrice);
        $side = $trade->side;

        // P&L hesapla
        $pnl = $side === 'BUY'
            ? ($currentPrice - $entryPrice) / $entryPrice
            : ($entryPrice - $currentPrice) / $entryPrice;

        $pnlPercent = $pnl * 100.0;

        // Risk seviyesi belirle
        $riskLevel = 'LOW';
        if ($pnlPercent <= -5.0) {
            $riskLevel = 'HIGH';
        } elseif ($pnlPercent <= -2.0) {
            $riskLevel = 'MEDIUM';
        }

        // Stop loss mesafesi
        $slDistance = 0.0;
        if ($trade->stop_loss !== null) {
            $slDistance = $side === 'BUY'
                ? ($entryPrice - (float) $trade->stop_loss) / $entryPrice
                : ((float) $trade->stop_loss - $entryPrice) / $entryPrice;
            $slDistance *= 100.0;
        }

        // Öneriler
        $recommendations = [];
        if ($pnlPercent <= -5.0) {
            $recommendations[] = 'Consider closing position to limit losses';
        }
        if ($slDistance > 10.0) {
            $recommendations[] = 'Stop loss may be too far - consider tightening';
        }
        if ($pnlPercent >= 10.0) {
            $recommendations[] = 'Consider taking partial profits';
        }

        return [
            'risk_level' => $riskLevel,
            'details' => [
                'entry_price' => $entryPrice,
                'current_price' => $currentPrice,
                'pnl_percent' => round($pnlPercent, 2),
                'stop_loss_distance' => round($slDistance, 2),
                'position_size' => (float) $trade->qty,
                'side' => $side,
            ],
            'recommendations' => $recommendations,
        ];
    }
}
