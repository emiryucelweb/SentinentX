<?php

declare(strict_types=1);

namespace App\Services\Trading;

use Illuminate\Support\Facades\Log;

/**
 * Order Policy Manager - IOC Policy Implementation
 *
 * Rule: PostOnly → (reject) → Limit IOC (limit = best_price ± cap) → TWAP
 * Market IOC only under guard conditions; abort on cap violation
 */
class OrderPolicyManager
{
    private const DEFAULT_SLIPPAGE_CAP_BPS = 50; // 0.5%

    private const TWAP_CHUNK_SIZE = 0.25; // 25% of quantity per chunk

    private const GUARD_CONDITIONS = ['EXTREME_VOLATILITY', 'LIQUIDITY_CRISIS', 'EMERGENCY_EXIT'];

    public function __construct(
        private readonly MarketDataService $marketData
    ) {}

    /**
     * Execute order with IOC policy
     */
    public function executeOrder(array $orderRequest): array
    {
        $symbol = $orderRequest['symbol'];
        $side = $orderRequest['side'];
        $quantity = $orderRequest['quantity'];
        $slippageCapBps = $orderRequest['slippage_cap_bps'] ?? self::DEFAULT_SLIPPAGE_CAP_BPS;

        Log::info('IOC Policy: Order execution started', [
            'event' => 'IOC_POLICY_STARTED',
            'decision_id' => uniqid('ioc_', true),
            'symbol' => $symbol,
            'side' => $side,
            'quantity' => $quantity,
            'slippage_cap_bps' => $slippageCapBps,
            'order_mode' => 'IOC_POLICY',
            'timestamp' => microtime(true),
        ]);

        // Step 1: Try PostOnly first
        $postOnlyResult = $this->tryPostOnly($orderRequest);
        if ($postOnlyResult['success']) {
            return $postOnlyResult;
        }

        Log::info('IOC Policy: PostOnly rejected, proceeding to Limit IOC', [
            'symbol' => $symbol,
            'reject_reason' => $postOnlyResult['reason'],
        ]);

        // Step 2: Calculate Limit IOC with slippage cap
        $limitIocResult = $this->tryLimitIOC($orderRequest, $slippageCapBps);
        if ($limitIocResult['success']) {
            return $limitIocResult;
        }

        // Step 3: Check if Market IOC is allowed under guard conditions
        if ($this->isMarketIOCGuarded($symbol)) {
            Log::warning('IOC Policy: Market IOC attempted but guard conditions not met', [
                'symbol' => $symbol,
                'guard_conditions' => self::GUARD_CONDITIONS,
                'abort_reason' => 'MARKET_IOC_NOT_GUARDED',
            ]);

            return [
                'success' => false,
                'order_mode' => 'MARKET_IOC_BLOCKED',
                'abort_reason' => 'MARKET_IOC_NOT_GUARDED',
                'fallback' => 'TWAP_RECOMMENDED',
            ];
        }

        // Step 4: Fallback to TWAP
        return $this->executeTWAP($orderRequest);
    }

    /**
     * Try PostOnly order first
     */
    private function tryPostOnly(array $orderRequest): array
    {
        $symbol = $orderRequest['symbol'];
        $side = $orderRequest['side'];
        $quantity = $orderRequest['quantity'];

        // Simulate PostOnly order (would integrate with exchange API)
        $bestPrice = $this->marketData->getBestPrice($symbol, $side);

        Log::info('IOC Policy: PostOnly attempt', [
            'event' => 'POST_ONLY_SUBMITTED',
            'symbol' => $symbol,
            'side' => $side,
            'quantity' => $quantity,
            'best_price' => $bestPrice,
            'order_type' => 'POST_ONLY',
            'timestamp' => microtime(true),
        ]);

        // Simulate rejection (in real implementation, this would be exchange response)
        if (rand(1, 100) > 30) { // 70% rejection rate for simulation
            Log::info('IOC Policy: PostOnly rejected', [
                'event' => 'POST_ONLY_REJECTED',
                'symbol' => $symbol,
                'reason' => 'REJECTED_BY_EXCHANGE',
                'timestamp' => microtime(true),
            ]);

            return [
                'success' => false,
                'order_mode' => 'POST_ONLY',
                'reason' => 'POST_ONLY_REJECTED_BY_EXCHANGE',
            ];
        }

        return [
            'success' => true,
            'order_mode' => 'POST_ONLY',
            'filled_quantity' => $quantity,
            'average_price' => $bestPrice,
        ];
    }

    /**
     * Try Limit IOC with slippage cap
     */
    private function tryLimitIOC(array $orderRequest, int $slippageCapBps): array
    {
        $symbol = $orderRequest['symbol'];
        $side = $orderRequest['side'];
        $quantity = $orderRequest['quantity'];

        $bestPrice = $this->marketData->getBestPrice($symbol, $side);
        $slippageAmount = $bestPrice * ($slippageCapBps / 10000);

        $limitPrice = $side === 'buy'
            ? $bestPrice + $slippageAmount  // Buy: allow higher price
            : $bestPrice - $slippageAmount; // Sell: allow lower price

        Log::info('IOC Policy: Limit IOC attempt', [
            'event' => 'LIMIT_IOC_SUBMITTED',
            'symbol' => $symbol,
            'side' => $side,
            'quantity' => $quantity,
            'best_price' => $bestPrice,
            'limit_price' => $limitPrice,
            'slippage_cap_bps' => $slippageCapBps,
            'acceptable_slippage' => $slippageAmount,
            'order_type' => 'LIMIT_IOC',
            'timestamp' => microtime(true),
        ]);

        // Check if slippage cap would be violated
        $currentMarketPrice = $this->marketData->getCurrentPrice($symbol);
        $actualSlippage = abs($currentMarketPrice - $bestPrice) / $bestPrice * 10000;

        if ($actualSlippage > $slippageCapBps) {
            Log::warning('IOC Policy: Slippage cap violation detected', [
                'event' => 'SLIPPAGE_CAP_ENFORCED',
                'symbol' => $symbol,
                'actual_slippage_bps' => $actualSlippage,
                'cap_bps' => $slippageCapBps,
                'abort_reason' => 'SLIPPAGE_CAP_ENFORCED',
                'timestamp' => microtime(true),
            ]);

            return [
                'success' => false,
                'order_mode' => 'LIMIT_IOC',
                'abort_reason' => 'SLIPPAGE_CAP_ENFORCED',
                'actual_slippage_bps' => $actualSlippage,
                'cap_bps' => $slippageCapBps,
            ];
        }

        // Simulate partial fill (common with IOC)
        $fillRatio = rand(50, 100) / 100; // 50-100% fill
        $filledQuantity = $quantity * $fillRatio;

        Log::info('IOC Policy: Limit IOC executed', [
            'symbol' => $symbol,
            'filled_quantity' => $filledQuantity,
            'unfilled_quantity' => $quantity - $filledQuantity,
            'average_price' => $limitPrice,
            'fill_ratio' => $fillRatio,
        ]);

        return [
            'success' => true,
            'order_mode' => 'LIMIT_IOC',
            'filled_quantity' => $filledQuantity,
            'unfilled_quantity' => $quantity - $filledQuantity,
            'average_price' => $limitPrice,
            'requires_twap' => $filledQuantity < $quantity,
        ];
    }

    /**
     * Check if Market IOC is allowed under guard conditions
     */
    private function isMarketIOCGuarded(string $symbol): bool
    {
        // Check for guard conditions that allow Market IOC
        $volatility = $this->marketData->getVolatility($symbol);
        $liquidity = $this->marketData->getLiquidityScore($symbol);

        $isExtremeVolatility = $volatility > 0.05; // 5% volatility
        $isLiquidityCrisis = $liquidity < 0.3; // Low liquidity score

        $guardConditions = [
            'EXTREME_VOLATILITY' => $isExtremeVolatility,
            'LIQUIDITY_CRISIS' => $isLiquidityCrisis,
            'EMERGENCY_EXIT' => false, // Would be set by risk management
        ];

        $isGuarded = in_array(true, $guardConditions, true);

        Log::info('IOC Policy: Market IOC guard check', [
            'event' => 'MARKET_IOC_GUARDED',
            'symbol' => $symbol,
            'guard_conditions' => $guardConditions,
            'is_guarded' => $isGuarded,
            'timestamp' => microtime(true),
        ]);

        return ! $isGuarded; // Return false if any guard condition is active
    }

    /**
     * Execute TWAP for unfilled quantity
     */
    public function executeTWAP(array $orderRequest): array
    {
        $symbol = $orderRequest['symbol'];
        $side = $orderRequest['side'];
        $quantity = $orderRequest['quantity'];
        $unfilled = $orderRequest['unfilled_quantity'] ?? $quantity;
        $totalChunks = ceil($unfilled / ($quantity * self::TWAP_CHUNK_SIZE));

        Log::info('IOC Policy: TWAP execution started', [
            'event' => 'TWAP_START',
            'symbol' => $symbol,
            'side' => $side,
            'total_quantity' => $quantity,
            'unfilled_quantity' => $unfilled,
            'chunk_size' => self::TWAP_CHUNK_SIZE,
            'total_chunks' => $totalChunks,
            'order_mode' => 'TWAP',
            'timestamp' => microtime(true),
        ]);

        $chunkSize = $unfilled * self::TWAP_CHUNK_SIZE;
        $totalChunks = ceil($unfilled / $chunkSize);

        return [
            'success' => true,
            'order_mode' => 'TWAP',
            'total_quantity' => $quantity,
            'twap_quantity' => $unfilled,
            'chunk_size' => $chunkSize,
            'total_chunks' => $totalChunks,
            'estimated_duration_minutes' => $totalChunks * 2, // 2 minutes per chunk
        ];
    }

    /**
     * Get order policy statistics
     */
    public function getPolicyStats(): array
    {
        return [
            'default_slippage_cap_bps' => self::DEFAULT_SLIPPAGE_CAP_BPS,
            'twap_chunk_size' => self::TWAP_CHUNK_SIZE,
            'guard_conditions' => self::GUARD_CONDITIONS,
            'policy_flow' => 'PostOnly → Limit IOC (+cap) → TWAP',
            'market_ioc_policy' => 'Guard conditions only',
        ];
    }
}
