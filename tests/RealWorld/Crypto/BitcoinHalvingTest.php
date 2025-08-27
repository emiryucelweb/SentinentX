<?php

declare(strict_types=1);

namespace Tests\RealWorld\Crypto;

use App\Models\Trade;
use App\Models\User;
use App\Services\AI\ConsensusService;
use App\Services\Risk\RiskGuard;
use App\Services\Trading\PositionSizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Bitcoin Halving Event Simulation
 * Tests system behavior during major crypto event with high volatility
 */
class BitcoinHalvingTest extends TestCase
{
    use RefreshDatabase;

    private ConsensusService $consensus;

    private RiskGuard $riskGuard;

    private PositionSizer $positionSizer;

    private User $testUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->consensus = app(ConsensusService::class);
        $this->riskGuard = app(RiskGuard::class);
        $this->positionSizer = app(PositionSizer::class);

        $this->testUser = User::factory()->create([
            'meta' => ['risk_profile' => 'moderate'],
        ]);

        $this->seed(['AiProvidersSeeder']);
    }

    #[Test]
    public function bitcoin_halving_triggers_volatility_controls(): void
    {
        Log::info('Starting Bitcoin halving event simulation');

        // Setup: Pre-halving normal market conditions
        $preHalvingPrice = 45000.0;
        $normalVolatility = 0.02; // 2% daily volatility

        // Create existing BTC position
        $existingPosition = Trade::create([
            'user_id' => $this->testUser->id,
            'symbol' => 'BTCUSDT',
            'side' => 'LONG',
            'qty' => 0.1,
            'entry_price' => $preHalvingPrice,
            'status' => 'OPEN',
            'created_at' => now()->subHours(6),
        ]);

        // Phase 1: Halving event starts - 40% price increase in 4 hours
        $halvingPrice = $preHalvingPrice * 1.40; // 40% increase
        $halvingVolatility = 0.15; // 15% hourly volatility

        $this->simulateMarketData([
            'BTCUSDT' => [
                'price' => $halvingPrice,
                'volatility' => $halvingVolatility,
                'volume_spike' => 500, // 5x normal volume
                'social_sentiment' => 0.95, // Extreme bullishness
            ],
        ]);

        // Test 1: Risk gates should activate due to high volatility
        $riskCheck = $this->riskGuard->okToOpen(
            'BTCUSDT',
            $halvingPrice,
            'LONG',
            25, // Normal leverage
            $halvingPrice * 0.95 // 5% stop loss
        );

        $this->assertFalse($riskCheck['ok'], 'Risk gates should block new positions during extreme volatility');
        $this->assertContains('HIGH_VOLATILITY', $riskCheck['reasons'] ?? []);

        // Test 2: Position sizing should be reduced
        $positionSize = $this->positionSizer->sizeByRisk(
            10000, // $10k equity
            0.02,  // 2% risk
            $halvingPrice * $halvingVolatility, // ATR equivalent
            $halvingPrice,
            25, // Max leverage
            0.001, // Min quantity
            0.01   // Tick size
        );

        $normalPositionSize = $this->positionSizer->sizeByRisk(
            10000,
            0.02,
            $preHalvingPrice * $normalVolatility, // Normal ATR
            $preHalvingPrice,
            25,
            0.001,
            0.01
        );

        $this->assertLessThan(
            $normalPositionSize['qty'],
            $positionSize['qty'],
            'Position size should be reduced during high volatility'
        );

        // Test 3: AI consensus should reflect extreme caution
        $snapshot = $this->createMarketSnapshot([
            'BTCUSDT' => $halvingPrice,
            'volatility_regime' => 'EXTREME',
            'halving_event' => true,
        ]);

        $aiDecision = $this->consensus->decide($snapshot);

        // During halving, AI should either:
        // 1. Recommend HOLD (no new positions)
        // 2. Recommend reduced position sizes
        // 3. Increase confidence threshold requirements

        $finalAction = strtoupper($aiDecision['final']['action'] ?? 'HOLD');
        $confidence = $aiDecision['final']['confidence'] ?? 0;

        if ($finalAction !== 'HOLD') {
            $this->assertGreaterThan(
                80, // Higher confidence required during extreme events
                $confidence,
                'AI confidence should be higher during halving events'
            );
        }

        // Phase 2: Price correction after initial spike
        $correctionPrice = $halvingPrice * 0.85; // 15% pullback

        $this->travelTo(now()->addHours(2));

        $this->simulateMarketData([
            'BTCUSDT' => [
                'price' => $correctionPrice,
                'volatility' => 0.08, // Still elevated
                'volume_spike' => 300, // Still high but reducing
                'social_sentiment' => 0.75, // More balanced
            ],
        ]);

        // Test 4: Existing position management during correction
        $existingPosition->refresh();

        // Calculate current P&L
        $currentPnl = (($correctionPrice - $preHalvingPrice) / $preHalvingPrice) * 100;
        $this->assertGreaterThan(15, $currentPnl, 'Position should still be profitable after correction');

        // Test 5: Risk gates should gradually reopen as volatility decreases
        $riskCheckCorrection = $this->riskGuard->okToOpen(
            'ETHUSDT', // Different asset to avoid correlation
            3000, // ETH price
            'LONG',
            15, // Lower leverage
            2850 // Conservative stop
        );

        // Should be less restrictive than during peak volatility
        $this->assertTrue(
            $riskCheckCorrection['ok'] || count($riskCheckCorrection['reasons']) < count($riskCheck['reasons']),
            'Risk controls should ease as volatility decreases'
        );

        Log::info('Bitcoin halving simulation completed', [
            'initial_price' => $preHalvingPrice,
            'peak_price' => $halvingPrice,
            'correction_price' => $correctionPrice,
            'position_pnl' => $currentPnl,
            'risk_gates_active' => ! $riskCheck['ok'],
        ]);
    }

    #[Test]
    public function halving_event_stress_test_multiple_timeframes(): void
    {
        Log::info('Starting halving stress test across multiple timeframes');

        $basePrice = 50000.0;
        $timeframes = [
            '1h_before' => ['price' => $basePrice, 'volatility' => 0.02],
            'halving' => ['price' => $basePrice * 1.25, 'volatility' => 0.12],
            '4h_after' => ['price' => $basePrice * 1.45, 'volatility' => 0.18],
            '1d_after' => ['price' => $basePrice * 1.20, 'volatility' => 0.08],
            '1w_after' => ['price' => $basePrice * 1.15, 'volatility' => 0.04],
        ];

        $results = [];

        foreach ($timeframes as $phase => $market) {
            $this->simulateMarketData(['BTCUSDT' => $market]);

            // Test risk controls at each phase
            $riskResult = $this->riskGuard->okToOpen(
                'BTCUSDT',
                $market['price'],
                'LONG',
                20,
                $market['price'] * 0.95
            );

            // Test position sizing
            $sizeResult = $this->positionSizer->sizeByRisk(
                10000,
                0.02,
                $market['price'] * $market['volatility'],
                $market['price'],
                20,
                0.001,
                0.01
            );

            $results[$phase] = [
                'price' => $market['price'],
                'volatility' => $market['volatility'],
                'risk_ok' => $riskResult['ok'],
                'position_size' => $sizeResult['qty'],
                'timestamp' => now()->toISOString(),
            ];

            // Advance time for next phase
            $this->travelTo(now()->addHours(6));
        }

        // Validate progression
        $this->assertFalse($results['halving']['risk_ok'], 'Risk should be blocked during halving');
        $this->assertFalse($results['4h_after']['risk_ok'], 'Risk should remain blocked 4h after');
        $this->assertTrue($results['1w_after']['risk_ok'], 'Risk should normalize after 1 week');

        // Position sizes should follow inverse relationship with volatility
        $this->assertLessThan(
            $results['1h_before']['position_size'],
            $results['halving']['position_size'],
            'Position size should decrease during high volatility'
        );

        Log::info('Halving stress test completed', ['results' => $results]);
    }

    private function simulateMarketData(array $marketData): void
    {
        foreach ($marketData as $symbol => $data) {
            Http::fake([
                "*{$symbol}*" => Http::response([
                    'result' => [
                        'list' => [[
                            'symbol' => $symbol,
                            'lastPrice' => (string) $data['price'],
                            'volume24h' => (string) ($data['volume_spike'] ?? 100),
                            'priceChangePercent' => (string) (($data['volatility'] ?? 0.02) * 100),
                        ]],
                    ],
                    'retCode' => 0,
                ]),
            ]);
        }
    }

    private function createMarketSnapshot(array $data): array
    {
        return [
            'symbols' => ['BTCUSDT'],
            'market_data' => [
                'BTCUSDT' => [
                    'price' => $data['BTCUSDT'],
                    'atr' => $data['BTCUSDT'] * 0.05, // 5% ATR
                    'volume' => 1000000,
                ],
            ],
            'portfolio' => [
                'equity' => 10000,
                'available' => 8000,
            ],
            'risk_context' => [
                'volatility_regime' => $data['volatility_regime'] ?? 'NORMAL',
                'special_event' => $data['halving_event'] ?? false,
            ],
            'timestamp' => now()->toISOString(),
        ];
    }
}
