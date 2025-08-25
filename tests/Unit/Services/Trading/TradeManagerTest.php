<?php

namespace Tests\Unit\Services\Trading;

use App\Services\Trading\TradeManager;
use App\Contracts\Exchange\ExchangeClientInterface;
use App\Services\Trading\PositionSizer;
use App\Services\Trading\StopCalculator;
use App\Services\Exchange\InstrumentInfoService;
use App\Services\Exchange\AccountService;
use App\Contracts\Risk\RiskGuardInterface;
use Tests\TestCase;
use Mockery;

class TradeManagerTest extends TestCase
{
    private TradeManager $tradeManager;
    private $exchange;
    private $info;
    private $account;
    private $sizer;
    private $stopCalc;
    private $risk;

    protected function setUp(): void
    {
        parent::setUp();

        // Create mocks for all dependencies
        $this->exchange = Mockery::mock(ExchangeClientInterface::class);
        $this->info = Mockery::mock(InstrumentInfoService::class);
        $this->account = Mockery::mock(AccountService::class);
        $this->sizer = Mockery::mock(PositionSizer::class);
        $this->stopCalc = Mockery::mock(StopCalculator::class);
        $this->risk = Mockery::mock(RiskGuardInterface::class);

        $this->tradeManager = new TradeManager(
            $this->exchange,
            $this->info,
            $this->account,
            $this->sizer,
            $this->stopCalc,
            $this->risk
        );
    }

    public function test_trade_manager_instantiation()
    {
        $this->assertInstanceOf(TradeManager::class, $this->tradeManager);
    }

    public function test_open_with_fallback_post_only_success()
    {
        $symbol = 'BTCUSDT';
        $action = 'LONG';
        $price = 50000.0;
        $qty = 0.001;
        $atrK = 1.5;

        // Mock exchange order creation (PostOnly success)
        $this->exchange->shouldReceive('createOrder')
            ->once()
            ->andReturn([
                'retCode' => 0,
                'retMsg' => 'OK',
                'result' => [
                    'orderId' => 'order123',
                    'orderStatus' => 'New'
                ]
            ]);

        $result = $this->tradeManager->openWithFallback($symbol, $action, $price, $qty, $atrK);

        $this->assertIsArray($result);
        $this->assertEquals('post_only', $result['attempt']);
        $this->assertEquals('order123', $result['orderId']);
    }

    public function test_open_with_fallback_handles_failure()
    {
        $symbol = 'BTCUSDT';
        $action = 'LONG';
        $price = 50000.0;
        $qty = 0.001;
        $atrK = 1.5;

        // Mock exchange failure (PostOnly fails, Market IOC also fails)
        $this->exchange->shouldReceive('createOrder')
            ->twice() // PostOnly + Market IOC attempts
            ->andReturn([
                'ok' => false,
                'error' => 'Insufficient balance'
            ]);

        $result = $this->tradeManager->openWithFallback($symbol, $action, $price, $qty, $atrK);

        $this->assertIsArray($result);
        $this->assertEquals('market_ioc', $result['attempt']);
        $this->assertNull($result['orderId']);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
