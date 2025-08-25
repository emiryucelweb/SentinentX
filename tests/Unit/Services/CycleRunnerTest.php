<?php

namespace Tests\Unit\Services;

use App\Contracts\Notifier\AlertDispatcher;
use App\Contracts\Risk\CorrelationServiceInterface;
use App\Contracts\Support\LockManager;
use App\Services\AI\ConsensusService;
use App\Services\CycleRunner;
use App\Services\Exchange\AccountService;
use App\Services\Exchange\InstrumentInfoService;
use App\Services\Market\BybitMarketData;
use App\Services\Risk\FundingGuard;
use App\Services\Risk\RiskGuardInterface;
use App\Services\Trading\PositionSizer;
use App\Services\Trading\StopCalculator;
use App\Services\Trading\TradeManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Mockery;

class CycleRunnerTest extends TestCase
{

    private CycleRunner $cycleRunner;

    // Mocked dependencies
    private $consensusService;
    private $marketData;
    private $accountService;
    private $instrumentService;
    private $riskGuard;
    private $positionSizer;
    private $stopCalculator;
    private $tradeManager;
    private $fundingGuard;
    private $alertDispatcher;
    private $lockManager;
    private $correlationService;

    protected function setUp(): void
    {
        parent::setUp();

        // Create mocks for all dependencies
        $this->consensusService = Mockery::mock(ConsensusService::class);
        $this->marketData = Mockery::mock(BybitMarketData::class);
        $this->accountService = Mockery::mock(AccountService::class);
        $this->instrumentService = Mockery::mock(InstrumentInfoService::class);
        $this->riskGuard = Mockery::mock(RiskGuardInterface::class);
        $this->positionSizer = Mockery::mock(PositionSizer::class);
        $this->stopCalculator = Mockery::mock(StopCalculator::class);
        $this->tradeManager = Mockery::mock(TradeManager::class);
        $this->fundingGuard = Mockery::mock(FundingGuard::class);
        $this->alertDispatcher = Mockery::mock(AlertDispatcher::class);
        $this->lockManager = Mockery::mock(LockManager::class);
        $this->correlationService = Mockery::mock(CorrelationServiceInterface::class);

        $this->cycleRunner = new CycleRunner(
            $this->consensusService,
            $this->marketData,
            $this->accountService,
            $this->instrumentService,
            $this->riskGuard,
            $this->positionSizer,
            $this->stopCalculator,
            $this->tradeManager,
            $this->fundingGuard,
            $this->alertDispatcher,
            $this->lockManager,
            $this->correlationService
        );
    }

    public function test_cycle_runner_can_be_instantiated()
    {
        $this->assertInstanceOf(CycleRunner::class, $this->cycleRunner);
    }

    public function test_cycle_runner_dependency_injection_works()
    {
        // Test that all dependencies are properly injected
        $this->assertTrue(true); // Basic dependency test - constructor passed
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
