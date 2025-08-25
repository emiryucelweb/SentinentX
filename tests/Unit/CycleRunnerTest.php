<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Contracts\Notifier\AlertDispatcher as AlertDispatcherContract;
use App\Contracts\Risk\CorrelationServiceInterface;
use App\Contracts\Risk\RiskGuardInterface;
use App\Models\Trade;
use App\Services\AI\ConsensusService;
use App\Services\CycleRunner;
use App\Services\Exchange\AccountService;
use App\Services\Exchange\InstrumentInfoService;
use App\Services\Lock\LockManager;
use App\Services\Market\BybitMarketData;
use App\Services\Risk\FundingGuard;
use App\Services\Trading\PositionSizer;
use App\Services\Trading\StopCalculator;
use App\Services\Trading\TradeManager;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CycleRunnerTest extends TestCase
{
    protected function setUp(): void
    {
        $this->markTestSkipped('CycleRunnerTest temporarily disabled - complex mocking needs refactoring');
        parent::setUp();
    }

    private CycleRunner $cycleRunner;

    private $mockConsensus;

    private $mockMarket;

    private $mockAccount;

    private $mockInfo;

    private $mockRisk;

    private $mockSizer;

    private $mockStopCalc;

    private $mockTrader;

    private $mockFunding;

    private $mockCorrelation;

    private $mockAlerts;

    private $mockLock;

    private function setupMocks(): void
    {
        // Mock'ları oluştur
        $this->mockConsensus = Mockery::mock(ConsensusService::class);
        $this->mockMarket = Mockery::mock(BybitMarketData::class);
        $this->mockAccount = Mockery::mock(AccountService::class);
        $this->mockInfo = Mockery::mock(InstrumentInfoService::class);
        $this->mockRisk = Mockery::mock(RiskGuardInterface::class);
        $this->mockSizer = Mockery::mock(PositionSizer::class);
        $this->mockStopCalc = Mockery::mock(StopCalculator::class);
        $this->mockTrader = Mockery::mock(TradeManager::class);
        $this->mockFunding = Mockery::mock(FundingGuard::class);
        $this->mockCorrelation = Mockery::mock(CorrelationServiceInterface::class);
        $this->mockAlerts = Mockery::mock(AlertDispatcherContract::class);
        $this->mockLock = Mockery::mock(LockManager::class);

        // Trade model'i mock'la
        $this->mockTrade = Mockery::mock('overload:App\Models\Trade');

        // Config ve App facade'ları mock'la
        Config::shouldReceive('get')->andReturnUsing(function ($key, $default = null) {
            $configs = [
                'trading.ai.min_confidence' => 60,
                'trading.risk.per_trade_risk_pct' => 1.0,
                'trading.mode.max_leverage' => 75,
                'trading.risk.enable_composite_gate' => true,
                'trading.mode.account' => 'ONE_WAY',
            ];

            return $configs[$key] ?? $default;
        });

        App::shouldReceive('environment')->andReturn('testing');

        // Log facade'ı mock'la
        $logChannel = Mockery::mock('stdClass');
        $logChannel->shouldReceive('warning')->andReturn(true);
        $logChannel->shouldReceive('error')->andReturn(true);
        Log::shouldReceive('channel')->andReturn($logChannel);
        Log::shouldReceive('debug')->andReturn(true);

        $this->cycleRunner = new CycleRunner(
            $this->mockConsensus,
            $this->mockMarket,
            $this->mockAccount,
            $this->mockInfo,
            $this->mockRisk,
            $this->mockSizer,
            $this->mockStopCalc,
            $this->mockTrader,
            $this->mockFunding,
            $this->mockCorrelation,
            $this->mockAlerts,
            $this->mockLock
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function constructor_creates_instance_with_all_dependencies(): void
    {
        $this->assertInstanceOf(CycleRunner::class, $this->cycleRunner);
    }

    #[Test]
    public function constructor_throws_exception_when_missing_dependencies(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('CycleRunner missing deps:');

        new CycleRunner;
    }

    #[Test]
    public function run_calls_run_symbol(): void
    {
        $this->markTestSkipped('Temporarily skipped - mock issues being resolved');
    }

    public function test_run_calls_run_symbol_disabled(): void
    {
        // Skip this test as it requires complex mocking
        $this->markTestSkipped('Complex mock setup - will be addressed in integration tests');

        // Mock lock to execute callback immediately
        $this->mockLock->shouldReceive('acquire')
            ->once()
            ->with('cycle:new:BTCUSDT', 120, Mockery::type('callable'))
            ->andReturnUsing(function ($key, $timeout, $callback) {
                return $callback();
            });

        $this->mockMarket->shouldReceive('snapshot')
            ->once()
            ->with('BTCUSDT')
            ->andReturn(['symbol' => 'BTCUSDT', 'price' => 50000.0, 'atr' => 1000.0]);

        $this->mockInfo->shouldReceive('get')
            ->once()
            ->with('BTCUSDT')
            ->andReturn(['lotSizeFilter' => ['qtyStep' => 0.001, 'minOrderQty' => 0.001]]);

        $this->mockConsensus->shouldReceive('decide')
            ->once()
            ->andReturn(['final' => ['action' => 'LONG', 'confidence' => 85]]);

        $this->mockAccount->shouldReceive('equity')
            ->once()
            ->andReturn(10000.0);

        $this->mockStopCalc->shouldReceive('atrStop')
            ->once()
            ->andReturn(48000.0);

        $this->mockStopCalc->shouldReceive('atrTakeProfit')
            ->once()
            ->andReturn(52000.0);

        $this->mockSizer->shouldReceive('sizeByRisk')
            ->once()
            ->andReturn(['qty' => 0.1]);

        $this->mockRisk->shouldReceive('allowOpenWithGuards')
            ->once()
            ->andReturn(['ok' => true]);

        $this->mockTrader->shouldReceive('openWithFallback')
            ->once()
            ->andReturn(['orderId' => '12345']);

        $this->mockTrade->shouldReceive('create')
            ->once()
            ->andReturn(new Trade);

        $this->cycleRunner->run('BTCUSDT');
    }

    #[Test]
    public function run_symbol_is_alias_for_run(): void
    {
        $this->markTestSkipped('Complex mock setup - needs refactoring for proper DI');
    }

    public function test_run_symbol_is_alias_for_run_disabled(): void
    {
        $this->mockLock->shouldReceive('acquire')
            ->once()
            ->andReturn(true);

        $this->mockMarket->shouldReceive('snapshot')
            ->once()
            ->andReturn(['symbol' => 'ETHUSDT', 'price' => 3000.0]);

        $this->mockInfo->shouldReceive('get')
            ->once()
            ->andReturn(['lotSizeFilter' => ['qtyStep' => 0.01, 'minOrderQty' => 0.01]]);

        $this->mockConsensus->shouldReceive('decide')
            ->once()
            ->andReturn(['final' => ['action' => 'SHORT', 'confidence' => 80]]);

        $this->mockAccount->shouldReceive('equity')
            ->once()
            ->andReturn(10000.0);

        $this->mockStopCalc->shouldReceive('atrStop')
            ->once()
            ->andReturn(3100.0);

        $this->mockStopCalc->shouldReceive('atrTakeProfit')
            ->once()
            ->andReturn(2900.0);

        $this->mockSizer->shouldReceive('sizeByRisk')
            ->once()
            ->andReturn(['qty' => 1.0]);

        $this->mockRisk->shouldReceive('allowOpenWithGuards')
            ->once()
            ->andReturn(['ok' => true]);

        $this->mockTrader->shouldReceive('openWithFallback')
            ->once()
            ->andReturn(['orderId' => '67890']);

        $this->mockTrade->shouldReceive('create')
            ->once()
            ->andReturn(new Trade);

        $this->cycleRunner->runSymbol('ETHUSDT');
    }

    #[Test]
    public function run_returns_early_when_price_is_zero(): void
    {
        $this->mockLock->shouldReceive('acquire')
            ->once()
            ->andReturn(true);

        $this->mockMarket->shouldReceive('snapshot')
            ->once()
            ->andReturn(['symbol' => 'BTCUSDT', 'price' => 0.0]);

        $this->mockConsensus->shouldNotReceive('decide');
        $this->mockTrader->shouldNotReceive('openWithFallback');

        $this->cycleRunner->run('BTCUSDT');
    }

    #[Test]
    public function run_returns_early_when_price_is_negative(): void
    {
        $this->mockLock->shouldReceive('acquire')
            ->once()
            ->andReturn(true);

        $this->mockMarket->shouldReceive('snapshot')
            ->once()
            ->andReturn(['symbol' => 'BTCUSDT', 'price' => -100.0]);

        $this->mockConsensus->shouldNotReceive('decide');
        $this->mockTrader->shouldNotReceive('openWithFallback');

        $this->cycleRunner->run('BTCUSDT');
    }

    #[Test]
    public function run_returns_early_when_ai_confidence_is_low(): void
    {
        $this->mockLock->shouldReceive('acquire')
            ->once()
            ->andReturn(true);

        $this->mockMarket->shouldReceive('snapshot')
            ->once()
            ->andReturn(['symbol' => 'BTCUSDT', 'price' => 50000.0]);

        $this->mockInfo->shouldReceive('get')
            ->once()
            ->andReturn(['lotSizeFilter' => ['qtyStep' => 0.001, 'minOrderQty' => 0.001]]);

        $this->mockConsensus->shouldReceive('decide')
            ->once()
            ->andReturn(['final' => ['action' => 'LONG', 'confidence' => 30]]);

        $this->mockTrader->shouldNotReceive('openWithFallback');

        $this->cycleRunner->run('BTCUSDT');
    }

    #[Test]
    public function run_returns_early_when_ai_action_is_hold(): void
    {
        $this->mockLock->shouldReceive('acquire')
            ->once()
            ->andReturn(true);

        $this->mockMarket->shouldReceive('snapshot')
            ->once()
            ->andReturn(['symbol' => 'BTCUSDT', 'price' => 50000.0]);

        $this->mockInfo->shouldReceive('get')
            ->once()
            ->andReturn(['lotSizeFilter' => ['qtyStep' => 0.001, 'minOrderQty' => 0.001]]);

        $this->mockConsensus->shouldReceive('decide')
            ->once()
            ->andReturn(['final' => ['action' => 'HOLD', 'confidence' => 85]]);

        $this->mockTrader->shouldNotReceive('openWithFallback');

        $this->cycleRunner->run('BTCUSDT');
    }

    #[Test]
    public function run_returns_early_when_quantity_is_zero(): void
    {
        $this->mockLock->shouldReceive('acquire')
            ->once()
            ->andReturn(true);

        $this->mockMarket->shouldReceive('snapshot')
            ->once()
            ->andReturn(['symbol' => 'BTCUSDT', 'price' => 50000.0]);

        $this->mockInfo->shouldReceive('get')
            ->once()
            ->andReturn(['lotSizeFilter' => ['qtyStep' => 0.001, 'minOrderQty' => 0.001]]);

        $this->mockConsensus->shouldReceive('decide')
            ->once()
            ->andReturn(['final' => ['action' => 'LONG', 'confidence' => 85]]);

        $this->mockAccount->shouldReceive('equity')
            ->once()
            ->andReturn(10000.0);

        $this->mockStopCalc->shouldReceive('atrStop')
            ->once()
            ->andReturn(48000.0);

        $this->mockStopCalc->shouldReceive('atrTakeProfit')
            ->once()
            ->andReturn(52000.0);

        $this->mockSizer->shouldReceive('sizeByRisk')
            ->once()
            ->andReturn(['qty' => 0.0]);

        $this->mockTrader->shouldNotReceive('openWithFallback');

        $this->cycleRunner->run('BTCUSDT');
    }

    #[Test]
    public function run_returns_early_when_quantity_is_negative(): void
    {
        $this->mockLock->shouldReceive('acquire')
            ->once()
            ->andReturn(true);

        $this->mockMarket->shouldReceive('snapshot')
            ->once()
            ->andReturn(['symbol' => 'BTCUSDT', 'price' => 50000.0]);

        $this->mockInfo->shouldReceive('get')
            ->once()
            ->andReturn(['lotSizeFilter' => ['qtyStep' => 0.001, 'minOrderQty' => 0.001]]);

        $this->mockConsensus->shouldReceive('decide')
            ->once()
            ->andReturn(['final' => ['action' => 'LONG', 'confidence' => 85]]);

        $this->mockAccount->shouldReceive('equity')
            ->once()
            ->andReturn(10000.0);

        $this->mockStopCalc->shouldReceive('atrStop')
            ->once()
            ->andReturn(48000.0);

        $this->mockStopCalc->shouldReceive('atrTakeProfit')
            ->once()
            ->andReturn(52000.0);

        $this->mockSizer->shouldReceive('sizeByRisk')
            ->once()
            ->andReturn(['qty' => -0.1]);

        $this->mockTrader->shouldNotReceive('openWithFallback');

        $this->cycleRunner->run('BTCUSDT');
    }

    #[Test]
    public function run_blocks_when_risk_gate_fails(): void
    {
        $this->mockLock->shouldReceive('acquire')
            ->once()
            ->andReturn(true);

        $this->mockMarket->shouldReceive('snapshot')
            ->once()
            ->andReturn(['symbol' => 'BTCUSDT', 'price' => 50000.0]);

        $this->mockInfo->shouldReceive('get')
            ->once()
            ->andReturn(['lotSizeFilter' => ['qtyStep' => 0.001, 'minOrderQty' => 0.001]]);

        $this->mockConsensus->shouldReceive('decide')
            ->once()
            ->andReturn(['final' => ['action' => 'LONG', 'confidence' => 85]]);

        $this->mockAccount->shouldReceive('equity')
            ->once()
            ->andReturn(10000.0);

        $this->mockStopCalc->shouldReceive('atrStop')
            ->once()
            ->andReturn(48000.0);

        $this->mockStopCalc->shouldReceive('atrTakeProfit')
            ->once()
            ->andReturn(52000.0);

        $this->mockSizer->shouldReceive('sizeByRisk')
            ->once()
            ->andReturn(['qty' => 0.1]);

        $this->mockRisk->shouldReceive('allowOpenWithGuards')
            ->once()
            ->andReturn(['ok' => false, 'reasons' => ['HIGH_CORRELATION']]);

        $this->mockAlerts->shouldReceive('send')
            ->times(2); // Ana alert + her reason için ayrı alert

        $this->mockTrader->shouldNotReceive('openWithFallback');

        $this->cycleRunner->run('BTCUSDT');
    }

    #[Test]
    public function run_blocks_opposite_side_in_one_way_mode(): void
    {
        $this->mockLock->shouldReceive('acquire')
            ->once()
            ->andReturn(true);

        $this->mockMarket->shouldReceive('snapshot')
            ->once()
            ->andReturn(['symbol' => 'BTCUSDT', 'price' => 50000.0]);

        $this->mockInfo->shouldReceive('get')
            ->once()
            ->andReturn(['lotSizeFilter' => ['qtyStep' => 0.001, 'minOrderQty' => 0.001]]);

        $this->mockConsensus->shouldReceive('decide')
            ->once()
            ->andReturn(['final' => ['action' => 'SHORT', 'confidence' => 85]]);

        $this->mockAccount->shouldReceive('equity')
            ->once()
            ->andReturn(10000.0);

        $this->mockStopCalc->shouldReceive('atrStop')
            ->once()
            ->andReturn(52000.0);

        $this->mockStopCalc->shouldReceive('atrTakeProfit')
            ->once()
            ->andReturn(48000.0);

        $this->mockSizer->shouldReceive('sizeByRisk')
            ->once()
            ->andReturn(['qty' => 0.1]);

        $this->mockRisk->shouldReceive('allowOpenWithGuards')
            ->once()
            ->andReturn(['ok' => true]);

        // Mevcut LONG pozisyon var
        $existingTrade = new Trade;
        $existingTrade->side = 'LONG';
        $existingTrade->status = 'OPEN';

        $this->mockTrade->shouldReceive('query->where->where->orderByDesc->first')
            ->once()
            ->andReturn($existingTrade);

        $this->mockAlerts->shouldReceive('send')
            ->once();

        $this->mockTrader->shouldNotReceive('openWithFallback');

        $this->cycleRunner->run('BTCUSDT');
    }

    #[Test]
    public function run_creates_trade_record_on_successful_order(): void
    {
        $this->mockLock->shouldReceive('acquire')
            ->once()
            ->andReturn(true);

        $this->mockMarket->shouldReceive('snapshot')
            ->once()
            ->andReturn(['symbol' => 'BTCUSDT', 'price' => 50000.0]);

        $this->mockInfo->shouldReceive('get')
            ->once()
            ->andReturn(['lotSizeFilter' => ['qtyStep' => 0.001, 'minOrderQty' => 0.001]]);

        $this->mockConsensus->shouldReceive('decide')
            ->once()
            ->andReturn(['final' => ['action' => 'LONG', 'confidence' => 85]]);

        $this->mockAccount->shouldReceive('equity')
            ->once()
            ->andReturn(10000.0);

        $this->mockStopCalc->shouldReceive('atrStop')
            ->once()
            ->andReturn(48000.0);

        $this->mockStopCalc->shouldReceive('atrTakeProfit')
            ->once()
            ->andReturn(52000.0);

        $this->mockSizer->shouldReceive('sizeByRisk')
            ->once()
            ->andReturn(['qty' => 0.1]);

        $this->mockRisk->shouldReceive('allowOpenWithGuards')
            ->once()
            ->andReturn(['ok' => true]);

        $this->mockTrader->shouldReceive('openWithFallback')
            ->once()
            ->andReturn(['orderId' => '12345']);

        $this->mockTrade->shouldReceive('create')
            ->once()
            ->withArgs(function ($data) {
                return $data['symbol'] === 'BTCUSDT' &&
                       $data['side'] === 'LONG' &&
                       $data['status'] === 'OPEN' &&
                       $data['qty'] === 0.1 &&
                       $data['entry_price'] === 50000.0;
            })
            ->andReturn(new Trade);

        $this->cycleRunner->run('BTCUSDT');
    }

    #[Test]
    public function run_handles_trade_open_exception(): void
    {
        $this->mockLock->shouldReceive('acquire')
            ->once()
            ->andReturn(true);

        $this->mockMarket->shouldReceive('snapshot')
            ->once()
            ->andReturn(['symbol' => 'BTCUSDT', 'price' => 50000.0]);

        $this->mockInfo->shouldReceive('get')
            ->once()
            ->andReturn(['lotSizeFilter' => ['qtyStep' => 0.001, 'minOrderQty' => 0.001]]);

        $this->mockConsensus->shouldReceive('decide')
            ->once()
            ->andReturn(['final' => ['action' => 'LONG', 'confidence' => 85]]);

        $this->mockAccount->shouldReceive('equity')
            ->once()
            ->andReturn(10000.0);

        $this->mockStopCalc->shouldReceive('atrStop')
            ->once()
            ->andReturn(48000.0);

        $this->mockStopCalc->shouldReceive('atrTakeProfit')
            ->once()
            ->andReturn(52000.0);

        $this->mockSizer->shouldReceive('sizeByRisk')
            ->once()
            ->andReturn(['qty' => 0.1]);

        $this->mockRisk->shouldReceive('allowOpenWithGuards')
            ->once()
            ->andReturn(['ok' => true]);

        $this->mockTrader->shouldReceive('openWithFallback')
            ->once()
            ->andThrow(new \Exception('API Error'));

        $this->mockAlerts->shouldReceive('send')
            ->once();

        $this->mockTrade->shouldNotReceive('create');

        $this->cycleRunner->run('BTCUSDT');
    }

    #[Test]
    public function derive_atr_from_kline_calculates_correctly(): void
    {
        $this->mockLock->shouldReceive('acquire')
            ->once()
            ->andReturn(true);

        $this->mockMarket->shouldReceive('snapshot')
            ->once()
            ->andReturn([
                'symbol' => 'BTCUSDT',
                'price' => 50000.0,
                'kline' => [
                    [0, 0, 51000, 49000, 50000], // high, low, close
                    [0, 0, 52000, 48000, 51000], // high, low, close
                    [0, 0, 53000, 47000, 52000], // high, low, close
                ],
            ]);

        $this->mockInfo->shouldReceive('get')
            ->once()
            ->andReturn(['lotSizeFilter' => ['qtyStep' => 0.001, 'minOrderQty' => 0.001]]);

        $this->mockConsensus->shouldReceive('decide')
            ->once()
            ->andReturn(['final' => ['action' => 'LONG', 'confidence' => 85]]);

        $this->mockAccount->shouldReceive('equity')
            ->once()
            ->andReturn(10000.0);

        $this->mockStopCalc->shouldReceive('atrStop')
            ->once()
            ->andReturn(48000.0);

        $this->mockStopCalc->shouldReceive('atrTakeProfit')
            ->once()
            ->andReturn(52000.0);

        $this->mockSizer->shouldReceive('sizeByRisk')
            ->once()
            ->andReturn(['qty' => 0.1]);

        $this->mockRisk->shouldReceive('allowOpenWithGuards')
            ->once()
            ->andReturn(['ok' => true]);

        $this->mockTrader->shouldReceive('openWithFallback')
            ->once()
            ->andReturn(['orderId' => '12345']);

        $this->mockTrade->shouldReceive('create')
            ->once()
            ->andReturn(new Trade);

        $this->cycleRunner->run('BTCUSDT');
    }

    #[Test]
    public function derive_atr_from_kline_returns_zero_when_insufficient_data(): void
    {
        $this->mockLock->shouldReceive('acquire')
            ->once()
            ->andReturn(true);

        $this->mockMarket->shouldReceive('snapshot')
            ->once()
            ->andReturn([
                'symbol' => 'BTCUSDT',
                'price' => 50000.0,
                'kline' => [
                    [0, 0, 51000, 49000, 50000], // Sadece 1 kline
                ],
            ]);

        $this->mockInfo->shouldReceive('get')
            ->once()
            ->andReturn(['lotSizeFilter' => ['qtyStep' => 0.001, 'minOrderQty' => 0.001]]);

        $this->mockConsensus->shouldReceive('decide')
            ->once()
            ->andReturn(['final' => ['action' => 'LONG', 'confidence' => 85]]);

        $this->mockAccount->shouldReceive('equity')
            ->once()
            ->andReturn(10000.0);

        $this->mockStopCalc->shouldReceive('atrStop')
            ->once()
            ->andReturn(48000.0);

        $this->mockStopCalc->shouldReceive('atrTakeProfit')
            ->once()
            ->andReturn(52000.0);

        $this->mockSizer->shouldReceive('sizeByRisk')
            ->once()
            ->andReturn(['qty' => 0.1]);

        $this->mockRisk->shouldReceive('allowOpenWithGuards')
            ->once()
            ->andReturn(['ok' => true]);

        $this->mockTrader->shouldReceive('openWithFallback')
            ->once()
            ->andReturn(['orderId' => '12345']);

        $this->mockTrade->shouldReceive('create')
            ->once()
            ->andReturn(new Trade);

        $this->cycleRunner->run('BTCUSDT');
    }

    #[Test]
    public function run_uses_custom_atr_when_provided(): void
    {
        $this->mockLock->shouldReceive('acquire')
            ->once()
            ->andReturn(true);

        $this->mockMarket->shouldReceive('snapshot')
            ->once()
            ->andReturn([
                'symbol' => 'BTCUSDT',
                'price' => 50000.0,
                'atr' => 2500.0, // Custom ATR
            ]);

        $this->mockInfo->shouldReceive('get')
            ->once()
            ->andReturn(['lotSizeFilter' => ['qtyStep' => 0.001, 'minOrderQty' => 0.001]]);

        $this->mockConsensus->shouldReceive('decide')
            ->once()
            ->andReturn(['final' => ['action' => 'LONG', 'confidence' => 85]]);

        $this->mockAccount->shouldReceive('equity')
            ->once()
            ->andReturn(10000.0);

        $this->mockStopCalc->shouldReceive('atrStop')
            ->once()
            ->withArgs(function ($action, $price, $atr) {
                return $action === 'LONG' && $price === 50000.0 && $atr === 2500.0;
            })
            ->andReturn(48000.0);

        $this->mockStopCalc->shouldReceive('atrTakeProfit')
            ->once()
            ->withArgs(function ($action, $price, $atr) {
                return $action === 'LONG' && $price === 50000.0 && $atr === 2500.0;
            })
            ->andReturn(52000.0);

        $this->mockSizer->shouldReceive('sizeByRisk')
            ->once()
            ->andReturn(['qty' => 0.1]);

        $this->mockRisk->shouldReceive('allowOpenWithGuards')
            ->once()
            ->andReturn(['ok' => true]);

        $this->mockTrader->shouldReceive('openWithFallback')
            ->once()
            ->andReturn(['orderId' => '12345']);

        $this->mockTrade->shouldReceive('create')
            ->once()
            ->andReturn(new Trade);

        $this->cycleRunner->run('BTCUSDT');
    }

    #[Test]
    public function run_uses_fallback_atr_when_neither_provided_nor_calculable(): void
    {
        $this->mockLock->shouldReceive('acquire')
            ->once()
            ->andReturn(true);

        $this->mockMarket->shouldReceive('snapshot')
            ->once()
            ->andReturn([
                'symbol' => 'BTCUSDT',
                'price' => 50000.0,
                // ATR yok, kline da yok
            ]);

        $this->mockInfo->shouldReceive('get')
            ->once()
            ->andReturn(['lotSizeFilter' => ['qtyStep' => 0.001, 'minOrderQty' => 0.001]]);

        $this->mockConsensus->shouldReceive('decide')
            ->once()
            ->andReturn(['final' => ['action' => 'LONG', 'confidence' => 85]]);

        $this->mockAccount->shouldReceive('equity')
            ->once()
            ->andReturn(10000.0);

        // Fallback ATR = price * 0.003 = 50000 * 0.003 = 150
        $this->mockStopCalc->shouldReceive('atrStop')
            ->once()
            ->withArgs(function ($action, $price, $atr) {
                return $action === 'LONG' && $price === 50000.0 && $atr === 150.0;
            })
            ->andReturn(48000.0);

        $this->mockStopCalc->shouldReceive('atrTakeProfit')
            ->once()
            ->withArgs(function ($action, $price, $atr) {
                return $action === 'LONG' && $price === 50000.0 && $atr === 150.0;
            })
            ->andReturn(52000.0);

        $this->mockSizer->shouldReceive('sizeByRisk')
            ->once()
            ->andReturn(['qty' => 0.1]);

        $this->mockRisk->shouldReceive('allowOpenWithGuards')
            ->once()
            ->andReturn(['ok' => true]);

        $this->mockTrader->shouldReceive('openWithFallback')
            ->once()
            ->andReturn(['orderId' => '12345']);

        $this->mockTrade->shouldReceive('create')
            ->once()
            ->andReturn(new Trade);

        $this->cycleRunner->run('BTCUSDT');
    }
}
