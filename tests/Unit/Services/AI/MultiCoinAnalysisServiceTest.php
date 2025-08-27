<?php

declare(strict_types=1);

namespace Tests\Unit\Services\AI;

use App\Models\User;
use App\Services\AI\ConsensusService;
use App\Services\AI\MultiCoinAnalysisService;
use App\Services\Market\BybitMarketData;
use App\Services\Market\CoinGeckoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class MultiCoinAnalysisServiceTest extends TestCase
{
    use RefreshDatabase;

    private MultiCoinAnalysisService $service;

    private $coinGeckoService;

    private $bybitMarketData;

    private $consensusService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->coinGeckoService = Mockery::mock(CoinGeckoService::class);
        $this->bybitMarketData = Mockery::mock(BybitMarketData::class);
        $this->consensusService = Mockery::mock(ConsensusService::class);

        $this->service = new MultiCoinAnalysisService(
            $this->coinGeckoService,
            $this->bybitMarketData,
            $this->consensusService
        );
    }

    public function test_analyzes_all_four_coins(): void
    {
        $user = User::factory()->create([
            'meta' => ['risk_profile' => 'moderate'],
        ]);

        // Mock CoinGecko data
        $this->coinGeckoService
            ->shouldReceive('getMultiCoinData')
            ->once()
            ->andReturn([
                'BTCUSDT' => [
                    'reliability_score' => 85.0,
                    'sentiment' => 65.0,
                    'current_price' => 43250.0,
                ],
                'ETHUSDT' => [
                    'reliability_score' => 80.0,
                    'sentiment' => 70.0,
                    'current_price' => 2650.0,
                ],
                'SOLUSDT' => [
                    'reliability_score' => 75.0,
                    'sentiment' => 60.0,
                    'current_price' => 98.5,
                ],
                'XRPUSDT' => [
                    'reliability_score' => 70.0,
                    'sentiment' => 55.0,
                    'current_price' => 0.58,
                ],
            ]);

        // Mock Bybit data for each coin
        foreach (['BTCUSDT', 'ETHUSDT', 'SOLUSDT', 'XRPUSDT'] as $symbol) {
            $this->bybitMarketData
                ->shouldReceive('getTicker')
                ->with($symbol)
                ->once()
                ->andReturn(['success' => true, 'data' => ['last_price' => 43250.0]]);

            $this->bybitMarketData
                ->shouldReceive('getKlines')
                ->with($symbol, '1', 50)
                ->once()
                ->andReturn(['success' => true, 'data' => []]);

            $this->bybitMarketData
                ->shouldReceive('getOrderbook')
                ->with($symbol, 25)
                ->once()
                ->andReturn(['success' => true, 'data' => []]);
        }

        // Mock AI consensus decisions
        $this->consensusService
            ->shouldReceive('decide')
            ->times(4)
            ->andReturn([
                'action' => 'LONG',
                'confidence' => 75,
                'reason' => 'Bullish sentiment detected',
            ]);

        $result = $this->service->analyzeAllCoins($user);

        $this->assertTrue($result['success']);
        $this->assertNotNull($result['selected_coin']);
        $this->assertArrayHasKey('all_analyses', $result);
        $this->assertCount(4, $result['all_analyses']);
        $this->assertArrayHasKey('market_overview', $result);
    }

    public function test_selects_most_reliable_coin(): void
    {
        $user = User::factory()->create();

        // Mock data with clear winner (BTC)
        $this->coinGeckoService
            ->shouldReceive('getMultiCoinData')
            ->once()
            ->andReturn([
                'BTCUSDT' => ['reliability_score' => 95.0, 'sentiment' => 80.0],
                'ETHUSDT' => ['reliability_score' => 70.0, 'sentiment' => 60.0],
                'SOLUSDT' => ['reliability_score' => 60.0, 'sentiment' => 50.0],
                'XRPUSDT' => ['reliability_score' => 50.0, 'sentiment' => 40.0],
            ]);

        // Mock Bybit and AI responses
        $this->mockBybitAndAiResponses();

        $result = $this->service->analyzeAllCoins($user);

        $this->assertEquals('BTCUSDT', $result['selected_coin']);
        $this->assertStringContains('Bitcoin', $result['selection_reason']);
    }

    public function test_handles_no_trading_signals(): void
    {
        $user = User::factory()->create();

        $this->coinGeckoService
            ->shouldReceive('getMultiCoinData')
            ->once()
            ->andReturn([
                'BTCUSDT' => ['reliability_score' => 50.0, 'sentiment' => 50.0],
                'ETHUSDT' => ['reliability_score' => 50.0, 'sentiment' => 50.0],
                'SOLUSDT' => ['reliability_score' => 50.0, 'sentiment' => 50.0],
                'XRPUSDT' => ['reliability_score' => 50.0, 'sentiment' => 50.0],
            ]);

        // Mock Bybit responses
        foreach (['BTCUSDT', 'ETHUSDT', 'SOLUSDT', 'XRPUSDT'] as $symbol) {
            $this->bybitMarketData
                ->shouldReceive('getTicker')
                ->with($symbol)
                ->once()
                ->andReturn(['success' => true, 'data' => ['last_price' => 100.0]]);

            $this->bybitMarketData
                ->shouldReceive('getKlines')
                ->with($symbol, '1', 50)
                ->once()
                ->andReturn(['success' => true, 'data' => []]);

            $this->bybitMarketData
                ->shouldReceive('getOrderbook')
                ->with($symbol, 25)
                ->once()
                ->andReturn(['success' => true, 'data' => []]);
        }

        // Mock AI returning NONE for all coins
        $this->consensusService
            ->shouldReceive('decide')
            ->times(4)
            ->andReturn([
                'action' => 'NONE',
                'confidence' => 30,
                'reason' => 'No clear signal',
            ]);

        $result = $this->service->analyzeAllCoins($user);

        $this->assertNull($result['selected_coin']);
        $this->assertStringContains('Hiçbir coin', $result['selection_reason']);
    }

    public function test_calculates_combined_score_correctly(): void
    {
        $user = User::factory()->create();

        // Mock high confidence AI decision with good market data
        $aiDecision = ['confidence' => 85, 'action' => 'LONG'];
        $marketData = ['reliability_score' => 90.0, 'sentiment_score' => 75.0];

        // Use reflection to test private method
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('calculateCombinedScore');
        $method->setAccessible(true);

        $score = $method->invoke($this->service, $aiDecision, $marketData);

        // Expected: (85 * 0.5) + (90 * 0.3) + ((75-50) * 0.2) = 42.5 + 27 + 5 = 74.5
        $this->assertEquals(74.5, $score);
    }

    public function test_includes_risk_profile_in_context(): void
    {
        $user = User::factory()->create([
            'meta' => ['risk_profile' => 'aggressive'],
        ]);

        $this->coinGeckoService
            ->shouldReceive('getMultiCoinData')
            ->once()
            ->andReturn([
                'BTCUSDT' => ['reliability_score' => 80.0, 'sentiment' => 60.0],
                'ETHUSDT' => ['reliability_score' => 75.0, 'sentiment' => 55.0],
                'SOLUSDT' => ['reliability_score' => 70.0, 'sentiment' => 50.0],
                'XRPUSDT' => ['reliability_score' => 65.0, 'sentiment' => 45.0],
            ]);

        $this->mockBybitAndAiResponses();

        $result = $this->service->analyzeAllCoins($user);

        $this->assertArrayHasKey('user_risk_profile', $result);
        $this->assertEquals('Yüksek Risk', $result['user_risk_profile']['name']);
    }

    private function mockBybitAndAiResponses(): void
    {
        foreach (['BTCUSDT', 'ETHUSDT', 'SOLUSDT', 'XRPUSDT'] as $symbol) {
            $this->bybitMarketData
                ->shouldReceive('getTicker')
                ->with($symbol)
                ->once()
                ->andReturn(['success' => true, 'data' => ['last_price' => 100.0]]);

            $this->bybitMarketData
                ->shouldReceive('getKlines')
                ->with($symbol, '1', 50)
                ->once()
                ->andReturn(['success' => true, 'data' => []]);

            $this->bybitMarketData
                ->shouldReceive('getOrderbook')
                ->with($symbol, 25)
                ->once()
                ->andReturn(['success' => true, 'data' => []]);
        }

        $this->consensusService
            ->shouldReceive('decide')
            ->times(4)
            ->andReturn([
                'action' => 'LONG',
                'confidence' => 75,
                'reason' => 'Good technical setup',
            ]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
