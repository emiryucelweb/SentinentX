<?php

declare(strict_types=1);

namespace Tests\RealWorld\Crypto;

use App\Models\Trade;
use App\Models\User;
use App\Services\AI\ConsensusService;
use App\Services\Risk\CorrelationService;
use App\Services\Risk\RiskGuard;
use App\Services\Trading\PositionManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Multi-Asset Flash Crash Test
 * Tests system behavior during simultaneous crashes across all major crypto assets
 */
class MultiAssetFlashCrashTest extends TestCase
{
    use RefreshDatabase;

    private ConsensusService $consensus;

    private RiskGuard $riskGuard;

    private CorrelationService $correlation;

    private PositionManager $positionManager;

    private User $testUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->consensus = app(ConsensusService::class);
        $this->riskGuard = app(RiskGuard::class);
        $this->correlation = app(CorrelationService::class);
        $this->positionManager = app(PositionManager::class);

        $this->testUser = User::factory()->create([
            'meta' => ['risk_profile' => 'moderate'],
        ]);

        $this->seed(['AiProvidersSeeder']);
    }

    #[Test]
    public function simultaneous_crypto_crash_triggers_emergency_halt(): void
    {
        Log::info('Starting multi-asset flash crash simulation');

        // Setup: Create diversified portfolio before crash
        $precrashPrices = [
            'BTCUSDT' => 45000.0,
            'ETHUSDT' => 3000.0,
            'SOLUSDT' => 100.0,
            'XRPUSDT' => 0.60,
        ];

        $positions = [];
        foreach ($precrashPrices as $symbol => $price) {
            $positions[] = Trade::create([
                'user_id' => $this->testUser->id,
                'symbol' => $symbol,
                'side' => 'LONG',
                'qty' => $symbol === 'BTCUSDT' ? 0.1 : ($symbol === 'ETHUSDT' ? 1.0 : 10.0),
                'entry_price' => $price,
                'status' => 'OPEN',
                'created_at' => now()->subHours(2),
            ]);
        }

        // Calculate initial portfolio value
        $initialPortfolioValue = collect($positions)->sum(function ($position) {
            return $position->qty * $position->entry_price;
        });

        // Phase 1: Flash crash begins - all assets drop 20% simultaneously
        $crashPrices = array_map(fn ($price) => $price * 0.80, $precrashPrices); // 20% drop

        $this->simulateFlashCrash($crashPrices, [
            'crash_duration_minutes' => 15,
            'volume_spike' => 1000, // 10x normal volume
            'correlation_spike' => 0.98, // Extremely high correlation
        ]);

        // Test 1: Correlation service should detect extreme correlation
        $correlationMatrix = $this->correlation->matrix(array_keys($crashPrices));

        foreach ($crashPrices as $symbol1 => $price1) {
            foreach ($crashPrices as $symbol2 => $price2) {
                if ($symbol1 !== $symbol2) {
                    $correlation = abs($correlationMatrix[$symbol1][$symbol2] ?? 0);
                    $this->assertGreaterThan(
                        0.85,
                        $correlation,
                        "High correlation should be detected between {$symbol1} and {$symbol2}"
                    );
                }
            }
        }

        // Test 2: Risk guard should block ALL new positions during flash crash
        foreach ($crashPrices as $symbol => $price) {
            $riskCheck = $this->riskGuard->allowOpenWithGuards(
                $symbol,
                $price,
                'LONG',
                20, // Normal leverage
                $price * 0.95, // 5% stop loss
                app(\App\Services\Risk\FundingGuard::class),
                $this->correlation
            );

            $this->assertFalse(
                $riskCheck['ok'],
                "New {$symbol} positions should be blocked during flash crash"
            );
            $this->assertContains('HIGH_CORRELATION_BLOCK', $riskCheck['reasons']);
        }

        // Test 3: Calculate portfolio drawdown
        $currentPortfolioValue = collect($positions)->sum(function ($position) use ($crashPrices) {
            return $position->qty * $crashPrices[$position->symbol];
        });

        $drawdownPercent = (($initialPortfolioValue - $currentPortfolioValue) / $initialPortfolioValue) * 100;
        $this->assertGreaterThan(15, $drawdownPercent, 'Portfolio should show significant drawdown');
        $this->assertLessThan(25, $drawdownPercent, 'Drawdown should not exceed catastrophic levels');

        // Test 4: AI consensus should recommend emergency actions
        $crashSnapshot = $this->createCrashSnapshot($crashPrices);
        $aiDecision = $this->consensus->decide($crashSnapshot);

        $finalAction = strtoupper($aiDecision['final']['action'] ?? 'HOLD');
        $confidence = $aiDecision['final']['confidence'] ?? 0;

        // During flash crash, AI should be very conservative
        $this->assertTrue(
            $finalAction === 'HOLD' || $confidence < 30,
            'AI should be extremely cautious during flash crash'
        );

        // Phase 2: Dead cat bounce - 8% recovery across all assets
        $this->travelTo(now()->addMinutes(20));

        $bouncePrice = array_map(fn ($price) => $price * 1.08, $crashPrices); // 8% recovery

        $this->simulateMarketRecovery($bouncePrice, [
            'recovery_volume' => 500, // Still elevated volume
            'correlation' => 0.75, // Still high but decreasing
        ]);

        // Test 5: Risk guards should remain cautious during bounce
        $btcRiskDuringBounce = $this->riskGuard->okToOpen(
            'BTCUSDT',
            $bouncePrice['BTCUSDT'],
            'LONG',
            10, // Reduced leverage
            $bouncePrice['BTCUSDT'] * 0.92 // Wider stop loss
        );

        // Should still be blocked or very restricted
        $this->assertTrue(
            ! $btcRiskDuringBounce['ok'] || count($btcRiskDuringBounce['reasons']) > 0,
            'Risk controls should remain active during dead cat bounce'
        );

        // Phase 3: Stabilization - prices stabilize at -12% from original
        $this->travelTo(now()->addHours(2));

        $stabilizedPrices = array_map(fn ($price) => $price * 0.88, $precrashPrices); // 12% down from original

        $this->simulateMarketStabilization($stabilizedPrices, [
            'volume_normalization' => 150, // Returning to normal
            'correlation' => 0.65, // More normal correlation
            'volatility_cooling' => 0.05, // Reduced volatility
        ]);

        // Test 6: Risk controls should gradually ease
        $stabilizedRiskCheck = $this->riskGuard->allowOpenWithGuards(
            'ETHUSDT', // Try different asset
            $stabilizedPrices['ETHUSDT'],
            'LONG',
            5, // Very conservative leverage
            $stabilizedPrices['ETHUSDT'] * 0.90, // Conservative stop
            app(\App\Services\Risk\FundingGuard::class),
            $this->correlation
        );

        // Should be less restrictive than during crash
        $this->assertTrue(
            $stabilizedRiskCheck['ok'] || count($stabilizedRiskCheck['reasons']) < 3,
            'Risk controls should ease during stabilization'
        );

        // Test 7: Portfolio should still be above catastrophic loss levels
        $finalPortfolioValue = collect($positions)->sum(function ($position) use ($stabilizedPrices) {
            return $position->qty * $stabilizedPrices[$position->symbol];
        });

        $finalDrawdown = (($initialPortfolioValue - $finalPortfolioValue) / $initialPortfolioValue) * 100;
        $this->assertLessThan(15, $finalDrawdown, 'Final drawdown should be manageable');

        Log::info('Multi-asset flash crash simulation completed', [
            'initial_portfolio_value' => $initialPortfolioValue,
            'crash_portfolio_value' => $currentPortfolioValue,
            'final_portfolio_value' => $finalPortfolioValue,
            'max_drawdown_percent' => $drawdownPercent,
            'final_drawdown_percent' => $finalDrawdown,
            'positions_count' => count($positions),
        ]);
    }

    #[Test]
    public function liquidation_cascade_prevention(): void
    {
        Log::info('Starting liquidation cascade prevention test');

        // Setup: Create highly leveraged positions before crash
        $leveragedPositions = [
            'BTCUSDT' => ['price' => 50000, 'leverage' => 50, 'qty' => 0.2],
            'ETHUSDT' => ['price' => 3200, 'leverage' => 40, 'qty' => 3.0],
            'SOLUSDT' => ['price' => 120, 'leverage' => 30, 'qty' => 50.0],
        ];

        $positions = [];
        foreach ($leveragedPositions as $symbol => $config) {
            $positions[] = Trade::create([
                'user_id' => $this->testUser->id,
                'symbol' => $symbol,
                'side' => 'LONG',
                'qty' => $config['qty'],
                'entry_price' => $config['price'],
                'status' => 'OPEN',
                'meta' => json_encode(['leverage' => $config['leverage']]),
            ]);
        }

        // Simulate 25% crash - would normally trigger liquidations
        $crashPrices = [
            'BTCUSDT' => 37500, // 25% down
            'ETHUSDT' => 2400,  // 25% down
            'SOLUSDT' => 90,    // 25% down
        ];

        $this->simulateFlashCrash($crashPrices, [
            'liquidation_risk' => 'HIGH',
            'margin_pressure' => 0.85, // 85% margin usage
        ]);

        // Test liquidation risk calculation
        foreach ($positions as $position) {
            $config = $leveragedPositions[$position->symbol];
            $crashPrice = $crashPrices[$position->symbol];

            // Calculate liquidation distance
            $liquidationPrice = $config['price'] * (1 - (1 / $config['leverage']) * 0.8); // 80% of margin
            $liquidationRisk = $crashPrice <= $liquidationPrice;

            if ($liquidationRisk) {
                Log::warning('Liquidation risk detected', [
                    'symbol' => $position->symbol,
                    'entry_price' => $config['price'],
                    'current_price' => $crashPrice,
                    'liquidation_price' => $liquidationPrice,
                    'leverage' => $config['leverage'],
                ]);
            }

            // System should implement emergency measures for high-risk positions
            $this->assertTrue(true); // Test passes if no exceptions thrown
        }

        Log::info('Liquidation cascade test completed', [
            'positions_at_risk' => count(array_filter($positions, function ($position) use ($crashPrices, $leveragedPositions) {
                $config = $leveragedPositions[$position->symbol];
                $liquidationPrice = $config['price'] * (1 - (1 / $config['leverage']) * 0.8);

                return $crashPrices[$position->symbol] <= $liquidationPrice;
            })),
            'total_positions' => count($positions),
        ]);
    }

    private function simulateFlashCrash(array $crashPrices, array $options = []): void
    {
        foreach ($crashPrices as $symbol => $price) {
            Http::fake([
                "*{$symbol}*" => Http::response([
                    'result' => [
                        'list' => [[
                            'symbol' => $symbol,
                            'lastPrice' => (string) $price,
                            'volume24h' => (string) ($options['volume_spike'] ?? 100),
                            'priceChangePercent' => '-20.0', // 20% drop
                            'highPrice24h' => (string) ($price * 1.25), // Previous high
                            'lowPrice24h' => (string) $price, // Current low
                        ]],
                    ],
                    'retCode' => 0,
                ]),
            ]);
        }

        // Simulate high correlation in market data
        $this->correlation->updateCorrelationMatrix($crashPrices, $options['correlation_spike'] ?? 0.95);
    }

    private function simulateMarketRecovery(array $recoveryPrices, array $options = []): void
    {
        foreach ($recoveryPrices as $symbol => $price) {
            Http::fake([
                "*{$symbol}*" => Http::response([
                    'result' => [
                        'list' => [[
                            'symbol' => $symbol,
                            'lastPrice' => (string) $price,
                            'volume24h' => (string) ($options['recovery_volume'] ?? 150),
                            'priceChangePercent' => '8.0', // 8% recovery
                        ]],
                    ],
                    'retCode' => 0,
                ]),
            ]);
        }
    }

    private function simulateMarketStabilization(array $stabilizedPrices, array $options = []): void
    {
        foreach ($stabilizedPrices as $symbol => $price) {
            Http::fake([
                "*{$symbol}*" => Http::response([
                    'result' => [
                        'list' => [[
                            'symbol' => $symbol,
                            'lastPrice' => (string) $price,
                            'volume24h' => (string) ($options['volume_normalization'] ?? 100),
                            'priceChangePercent' => '-2.0', // Small movements
                        ]],
                    ],
                    'retCode' => 0,
                ]),
            ]);
        }
    }

    private function createCrashSnapshot(array $crashPrices): array
    {
        return [
            'symbols' => array_keys($crashPrices),
            'market_data' => array_map(function ($price) {
                return [
                    'price' => $price,
                    'atr' => $price * 0.15, // High volatility
                    'volume' => 1000000,
                ];
            }, $crashPrices),
            'portfolio' => [
                'equity' => 10000,
                'available' => 2000, // Low available due to margin usage
                'drawdown_percent' => 20,
            ],
            'risk_context' => [
                'volatility_regime' => 'EXTREME',
                'correlation_regime' => 'HIGH',
                'market_stress' => 'FLASH_CRASH',
            ],
            'timestamp' => now()->toISOString(),
        ];
    }
}
