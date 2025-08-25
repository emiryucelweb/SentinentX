<?php

declare(strict_types=1);

namespace App\Services\Trading;

use App\Services\Notifier\AlertDispatcher;
use App\Models\Trade;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;

/**
 * Yetim pozisyon mutabakatı.
 *  - Sarı: Borsada var, lokalde yok ⇒ otomatik trade oluştur + uyarı (YELLOW_ORPHAN_EXCHANGE)
 *  - Kırmızı: Lokalde açık görünüyor, borsada yok ⇒ trade CLOSE + uyarı (RED_ORPHAN_LOCAL)
 *  - Fee: Execution fee tracking ve eşleştirme
 */
final class ReconciliationService
{
    public function __construct(private readonly AlertDispatcher $alerts) {}

    /** @return array{yellow:int, red:int, fees_reconciled:int} */
    public function reconcile(array $exchangePositions, array $exchangeOrders = []): array
    {
        $yellow = 0;
        $red = 0;
        $feesReconciled = 0;

        // Key formatı: SYMBOL|LONG/SHORT
        $posMap = [];
        foreach ($exchangePositions as $p) {
            $sym = strtoupper((string) Arr::get($p, 'symbol'));
            $side = strtoupper((string) (
                Arr::get($p, 'side') === 'Sell'
                    ? 'SHORT'
                    : (Arr::get($p, 'side') === 'Buy' ? 'LONG' : Arr::get($p, 'side'))
            ));
            $size = (float) (Arr::get($p, 'size') ?? Arr::get($p, 'qty') ?? 0);
            if ($sym && $side && $size > 0) {
                $posMap[$sym.'|'.$side] = $p;
            }
        }

        // Lokal açık işlemler
        $open = Trade::query()->where('status', 'OPEN')->get();
        $localMap = [];
        foreach ($open as $t) {
            $localMap[strtoupper($t->symbol).'|'.strtoupper($t->side)] = $t;
        }

        // Sarı: borsada var, lokalde yok
        foreach ($posMap as $key => $p) {
            if (! isset($localMap[$key])) {
                $sym = strtoupper((string) Arr::get($p, 'symbol'));
                $side = strtoupper((string) (
                    Arr::get($p, 'side') === 'Sell' ? 'SHORT' : 'LONG'
                ));
                $qty = (float) (Arr::get($p, 'size') ?? Arr::get($p, 'qty') ?? 0);
                $entry = (float) (Arr::get($p, 'avgPrice') ?? Arr::get($p, 'entryPrice') ?? 0);

                Trade::create([
                    'symbol' => $sym,
                    'side' => $side,
                    'status' => 'OPEN',
                    'margin_mode' => 'CROSS',
                    'leverage' => (int) (Arr::get($p, 'leverage') ?? 1),
                    'qty' => $qty,
                    'entry_price' => $entry,
                    'opened_at' => Carbon::now(),
                    'meta' => json_encode(
                        ['reconciled' => true, 'source' => 'exchange'],
                        JSON_UNESCAPED_UNICODE
                    ),
                ]);

                $this->alerts->send(
                    'warn',
                    'YELLOW_ORPHAN_EXCHANGE',
                    "Borsada açık pozisyon bulundu; lokal senkronize edildi ($sym/$side)",
                    ['position' => $p]
                );
                $yellow++;
            }
        }

        // Kırmızı: lokalde açık, borsada yok
        foreach ($localMap as $key => $t) {
            if (! isset($posMap[$key])) {
                $t->status = 'CLOSED';
                $t->closed_at = Carbon::now();
                $t->save();
                $this->alerts->send(
                    'error',
                    'RED_ORPHAN_LOCAL',
                    "Lokal trade borsada bulunamadı; CLOSED yapıldı ({$t->symbol}/{$t->side})",
                    ['trade_id' => $t->id]
                );
                $red++;
            }
        }

        // Fee reconciliation
        $feesReconciled = $this->reconcileFees($exchangeOrders);

        return ['yellow' => $yellow, 'red' => $red, 'fees_reconciled' => $feesReconciled];
    }

    /**
     * Execution fee'leri eşleştir
     */
    private function reconcileFees(array $exchangeOrders): int
    {
        $feesReconciled = 0;

        foreach ($exchangeOrders as $order) {
            if (isset($order['execFee']) && $order['execFee'] > 0) {
                $symbol = strtoupper((string) Arr::get($order, 'symbol'));
                $orderId = Arr::get($order, 'orderId');

                // Trade'i bul ve fee'yi güncelle
                $trade = Trade::where('meta->exchange_order_id', $orderId)
                    ->orWhere('meta->order_id', $orderId)
                    ->first();

                if ($trade) {
                    $meta = json_decode($trade->meta ?? '{}', true);
                    $meta['execution_fee'] = (float) $order['execFee'];
                    $meta['fee_currency'] = Arr::get($order, 'feeCurrency', 'USDT');
                    $meta['fee_reconciled_at'] = Carbon::now()->toISOString();

                    $trade->update(['meta' => json_encode($meta, JSON_UNESCAPED_UNICODE)]);
                    $feesReconciled++;
                }
            }
        }

        return $feesReconciled;
    }

    /**
     * İlk reconciliation (initial sync)
     */
    public function initialSync(array $exchangePositions): array
    {
        $this->alerts->send(
            'info',
            'INITIAL_RECONCILIATION',
            'Initial reconciliation started',
            ['positions_count' => count($exchangePositions)]
        );

        return $this->reconcile($exchangePositions);
    }

    /**
     * Periyodik reconciliation (scheduled sync)
     */
    public function periodicSync(array $exchangePositions): array
    {
        $result = $this->reconcile($exchangePositions);

        if ($result['yellow'] > 0 || $result['red'] > 0) {
            $this->alerts->send(
                'warn',
                'RECONCILIATION_ISSUES',
                'Reconciliation issues detected during periodic sync',
                $result
            );
        }

        return $result;
    }
}
