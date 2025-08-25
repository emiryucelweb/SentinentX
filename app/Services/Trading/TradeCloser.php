<?php

declare(strict_types=1);

namespace App\Services\Trading;

use App\Services\Exchange\BybitClient;

final class TradeCloser
{
    public function __construct(private BybitClient $bybit) {}

    /** Tüm miktarı reduceOnly MARKET ile kapatır. */
    public function closeMarket(string $symbol, string $side, float $qty): array
    {
        // Kapatma için taraf tersine çevrilir
        $opp = strtoupper($side) === 'LONG' ? 'SELL' : 'BUY';

        return $this->bybit->createOrder($symbol, $opp, 'MARKET', $qty, null, [
            'reduceOnly' => true, 'timeInForce' => 'IOC',
        ]);
    }

    /**
     * SCALE-IN: Kısmi pozisyon kapatma
     */
    public function scaleIn(string $symbol, string $side, float $qty, float $price): array
    {
        $opp = strtoupper($side) === 'LONG' ? 'SELL' : 'BUY';

        return $this->bybit->createOrder($symbol, $opp, 'LIMIT', $qty, $price, [
            'reduceOnly' => true,
            'timeInForce' => 'GTC',
        ]);
    }

    /**
     * SCALE-OUT: Kısmi pozisyon kapatma (TP ladder)
     */
    public function scaleOut(string $symbol, string $side, float $qty, float $price): array
    {
        $opp = strtoupper($side) === 'LONG' ? 'SELL' : 'BUY';

        return $this->bybit->createOrder($symbol, $opp, 'LIMIT', $qty, $price, [
            'reduceOnly' => true,
            'timeInForce' => 'GTC',
        ]);
    }

    /**
     * OCO ile TP/SL kapatma
     */
    public function closeWithOco(string $symbol, string $side, float $qty, float $takeProfit, float $stopLoss): array
    {
        $opp = strtoupper($side) === 'LONG' ? 'SELL' : 'BUY';

        // OCO order oluştur
        return $this->bybit->createOcoOrder($symbol, $opp, $qty, $takeProfit, $stopLoss, [
            'reduceOnly' => true,
        ]);
    }

    /**
     * Partial TP ladder oluştur
     */
    public function createPartialTpLadder(string $symbol, string $side, float $totalQty, array $tpLevels): array
    {
        $orders = [];
        $remainingQty = $totalQty;

        foreach ($tpLevels as $level) {
            $qty = min($remainingQty, $level['qty']);
            if ($qty <= 0) {
                break;
            }

            $order = $this->scaleOut($symbol, $side, $qty, $level['price']);
            $orders[] = $order;
            $remainingQty -= $qty;
        }

        return [
            'orders' => $orders,
            'remaining_qty' => $remainingQty,
            'total_orders' => count($orders),
        ];
    }
}
