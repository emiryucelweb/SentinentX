<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\Risk\DriftGuardService;
use Tests\Fakes\FakeExchangeClient;
use Tests\Fakes\FakePnlService;
use Tests\TestCase;

final class DriftGuardServiceTest extends TestCase
{
    private DriftGuardService $service;

    private FakeExchangeClient $exchange;

    private FakePnlService $pnlService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->exchange = new FakeExchangeClient;
        $this->pnlService = new FakePnlService;
        $this->service = new DriftGuardService($this->exchange, $this->pnlService);
    }

    public function test_check_position_drift_long_profit(): void
    {
        $symbol = 'BTCUSDT';
        $side = 'LONG';
        $entryPrice = 50000;
        $currentPrice = 52500; // %5 profit
        $qty = 0.3; // Daha küçük pozisyon (15% of equity)
        $equity = 100000;

        $result = $this->service->checkPositionDrift($symbol, $side, $entryPrice, $currentPrice, $qty, $equity);

        $this->assertTrue($result['ok']); // HIGH risk (drift > 5% but position size < 20%)
        $this->assertEquals(5.0, $result['drift_pct']); // %5 profit
        $this->assertEquals(15.75, $result['position_size_pct']); // 15750/100000
        $this->assertEquals('HIGH', $result['risk_level']); // Drift > 5% threshold
        $this->assertContains('CONSIDER_TAKING_PROFITS', $result['actions']);
        // REDUCE_POSITION_SIZE sadece position size > 20% olduğunda eklenir
        $this->assertNotContains('REDUCE_POSITION_SIZE', $result['actions']);
    }

    public function test_check_position_drift_long_loss(): void
    {
        $symbol = 'BTCUSDT';
        $side = 'LONG';
        $entryPrice = 50000;
        $currentPrice = 47500; // %5 loss
        $qty = 0.3; // Küçük pozisyon (15% of equity)
        $equity = 100000;

        $result = $this->service->checkPositionDrift($symbol, $side, $entryPrice, $currentPrice, $qty, $equity);

        $this->assertTrue($result['ok']);
        $this->assertEquals(-5.0, $result['drift_pct']); // %5 loss
        $this->assertTrue(abs($result['position_size_pct'] - 14.25) < 0.001, 'Position size should be approximately 14.25'); // 14250/100000
        $this->assertEquals('HIGH', $result['risk_level']); // Drift >= 5% threshold
        $this->assertContains('CONSIDER_AVERAGING_DOWN', $result['actions']);
        // REDUCE_POSITION_SIZE sadece position size > 20% olduğunda eklenir
        $this->assertNotContains('REDUCE_POSITION_SIZE', $result['actions']);
    }

    public function test_check_position_drift_short_profit(): void
    {
        $symbol = 'BTCUSDT';
        $side = 'SHORT';
        $entryPrice = 50000;
        $currentPrice = 47500; // %5 profit for SHORT
        $qty = 0.3; // Küçük pozisyon (15% of equity)
        $equity = 100000;

        $result = $this->service->checkPositionDrift($symbol, $side, $entryPrice, $currentPrice, $qty, $equity);

        $this->assertTrue($result['ok']);
        $this->assertEquals(5.0, $result['drift_pct']); // %5 profit for SHORT
        $this->assertTrue(abs($result['position_size_pct'] - 14.25) < 0.001, 'Position size should be approximately 14.25');
        $this->assertEquals('HIGH', $result['risk_level']);
        $this->assertContains('CONSIDER_TAKING_PROFITS', $result['actions']);
    }

    public function test_check_position_drift_short_loss(): void
    {
        $symbol = 'BTCUSDT';
        $side = 'SHORT';
        $entryPrice = 50000;
        $currentPrice = 52500; // %5 loss for SHORT
        $qty = 0.3; // Küçük pozisyon (15% of equity)
        $equity = 100000;

        $result = $this->service->checkPositionDrift($symbol, $side, $entryPrice, $currentPrice, $qty, $equity);

        $this->assertTrue($result['ok']);
        $this->assertEquals(-5.0, $result['drift_pct']); // %5 loss for SHORT
        $this->assertEquals(15.75, $result['position_size_pct']);
        $this->assertEquals('HIGH', $result['risk_level']);
        $this->assertContains('CONSIDER_AVERAGING_DOWN', $result['actions']);
    }

    public function test_check_position_drift_low_risk(): void
    {
        $symbol = 'ETHUSDT';
        $side = 'LONG';
        $entryPrice = 3000;
        $currentPrice = 3030; // %1 profit
        $qty = 0.5;
        $equity = 100000;

        $result = $this->service->checkPositionDrift($symbol, $side, $entryPrice, $currentPrice, $qty, $equity);

        $this->assertTrue($result['ok']);
        $this->assertEquals(1.0, $result['drift_pct']); // %1 profit
        $this->assertTrue(abs($result['position_size_pct'] - 1.515) < 0.001, 'Position size should be approximately 1.515'); // 1515/100000
        $this->assertEquals('LOW', $result['risk_level']);
        $this->assertContains('CONTINUE_MONITORING', $result['actions']);
    }

    public function test_check_position_drift_medium_risk(): void
    {
        $symbol = 'ADAUSDT';
        $side = 'LONG';
        $entryPrice = 0.5;
        $currentPrice = 0.5175; // %3.5 profit (70% of 5% threshold)
        $qty = 1000;
        $equity = 100000;

        $result = $this->service->checkPositionDrift($symbol, $side, $entryPrice, $currentPrice, $qty, $equity);

        $this->assertTrue($result['ok']);
        $this->assertTrue(abs($result['drift_pct'] - 3.5) < 0.001, 'Drift should be approximately 3.5');
        $this->assertTrue(abs($result['position_size_pct'] - 0.5175) < 0.001, 'Position size should be approximately 0.5175. Got: '.$result['position_size_pct']);
        $this->assertEquals('LOW', $result['risk_level']); // %3.5 drift < %5 threshold
        $this->assertContains('CONTINUE_MONITORING', $result['actions']);
    }

    public function test_check_position_drift_critical_risk(): void
    {
        $symbol = 'BTCUSDT';
        $side = 'LONG';
        $entryPrice = 50000;
        $currentPrice = 47500; // %5 loss
        $qty = 2.0; // Büyük pozisyon
        $equity = 100000;

        $result = $this->service->checkPositionDrift($symbol, $side, $entryPrice, $currentPrice, $qty, $equity);

        $this->assertFalse($result['ok']); // CRITICAL risk
        $this->assertEquals(-5.0, $result['drift_pct']);
        $this->assertEquals(95.0, $result['position_size_pct']); // 95000/100000
        $this->assertEquals('CRITICAL', $result['risk_level']);
        $this->assertContains('IMMEDIATE_POSITION_REDUCTION', $result['actions']);
        $this->assertContains('EMERGENCY_EXIT_IF_NEEDED', $result['actions']);
    }

    public function test_check_position_drift_edge_case_zero_equity(): void
    {
        $symbol = 'BTCUSDT';
        $side = 'LONG';
        $entryPrice = 50000;
        $currentPrice = 50000;
        $qty = 1.0;
        $equity = 0;

        $result = $this->service->checkPositionDrift($symbol, $side, $entryPrice, $currentPrice, $qty, $equity);

        $this->assertTrue($result['ok']);
        $this->assertEquals(0.0, $result['drift_pct']);
        $this->assertEquals(INF, $result['position_size_pct']); // Division by zero
        $this->assertEquals('HIGH', $result['risk_level']); // Position size risk
    }

    public function test_check_position_drift_edge_case_very_small_position(): void
    {
        $symbol = 'BTCUSDT';
        $side = 'LONG';
        $entryPrice = 50000;
        $currentPrice = 50000;
        $qty = 0.000001; // Çok küçük pozisyon
        $equity = 100000;

        $result = $this->service->checkPositionDrift($symbol, $side, $entryPrice, $currentPrice, $qty, $equity);

        $this->assertTrue($result['ok']);
        $this->assertEquals(0.0, $result['drift_pct']);
        $this->assertTrue(abs($result['position_size_pct'] - 0.00005) < 0.00000001, 'Position size should be approximately 0.00005'); // 0.05/100000
        $this->assertEquals('LOW', $result['risk_level']);
    }

    public function test_setup_drift_alarm(): void
    {
        $symbol = 'BTCUSDT';
        $threshold = 3.0;

        $result = $this->service->setupDriftAlarm($symbol, $threshold);

        $this->assertTrue($result['ok']);
        $this->assertStringStartsWith('drift_', $result['alarm_id']);
        $this->assertEquals($symbol, $result['details']['symbol']);
        $this->assertEquals($threshold, $result['details']['threshold']);
        $this->assertEquals('DRIFT_ALERT', $result['details']['type']);
    }

    public function test_close_drift_alarm(): void
    {
        $alarmId = 'drift_test_123';

        $result = $this->service->closeDriftAlarm($alarmId);

        $this->assertTrue($result['ok']);
        $this->assertEquals($alarmId, $result['alarm_id']);
        $this->assertEquals('CLOSED', $result['status']);
    }

    public function test_check_bulk_drift_single_position(): void
    {
        $positions = [
            [
                'symbol' => 'BTCUSDT',
                'side' => 'LONG',
                'entry_price' => 50000,
                'current_price' => 52500,
                'qty' => 0.3, // Küçük pozisyon
            ],
        ];
        $equity = 100000;

        $result = $this->service->checkBulkDrift($positions, $equity);

        $this->assertTrue($result['ok']);
        $this->assertEquals('HIGH', $result['overall_risk']);
        $this->assertEquals(1, $result['total_positions']);
        $this->assertEquals(1, $result['risk_distribution']['HIGH']);
        $this->assertCount(1, $result['position_results']);
    }

    public function test_check_bulk_drift_multiple_positions(): void
    {
        $positions = [
            [
                'symbol' => 'BTCUSDT',
                'side' => 'LONG',
                'entry_price' => 50000,
                'current_price' => 52500, // %5 profit
                'qty' => 0.3, // Küçük pozisyon
            ],
            [
                'symbol' => 'ETHUSDT',
                'side' => 'SHORT',
                'entry_price' => 3000,
                'current_price' => 2850, // %5 profit
                'qty' => 3.0, // Küçük pozisyon
            ],
            [
                'symbol' => 'ADAUSDT',
                'side' => 'LONG',
                'entry_price' => 0.5,
                'current_price' => 0.5, // No drift
                'qty' => 100, // Küçük pozisyon
            ],
        ];
        $equity = 100000;

        $result = $this->service->checkBulkDrift($positions, $equity);

        $this->assertTrue($result['ok']);
        $this->assertEquals('HIGH', $result['overall_risk']);
        $this->assertEquals(3, $result['total_positions']);
        $this->assertEquals(2, $result['risk_distribution']['HIGH']);
        $this->assertEquals(1, $result['risk_distribution']['LOW']);
        $this->assertCount(3, $result['position_results']);
    }

    public function test_check_bulk_drift_critical_risk(): void
    {
        $positions = [
            [
                'symbol' => 'BTCUSDT',
                'side' => 'LONG',
                'entry_price' => 50000,
                'current_price' => 47500, // %5 loss
                'qty' => 2.0, // Büyük pozisyon
            ],
        ];
        $equity = 100000;

        $result = $this->service->checkBulkDrift($positions, $equity);

        $this->assertFalse($result['ok']); // CRITICAL risk
        $this->assertEquals('CRITICAL', $result['overall_risk']);
        $this->assertEquals(1, $result['risk_distribution']['CRITICAL']);
    }

    public function test_update_config(): void
    {
        $newConfig = [
            'maxDriftThreshold' => 3.0, // %3
            'maxPositionSizeThreshold' => 15.0, // %15
            'driftCheckInterval' => 600, // 10 dakika
        ];

        $result = $this->service->updateConfig($newConfig);

        $this->assertTrue($result['ok']);
        $this->assertEquals(3.0, $result['updated_config']['maxDriftThreshold']);
        $this->assertEquals(15.0, $result['updated_config']['maxPositionSizeThreshold']);
        $this->assertEquals(600, $result['updated_config']['driftCheckInterval']);

        // Yeni konfigürasyonla test et
        $driftResult = $this->service->checkPositionDrift(
            'BTCUSDT',
            'LONG',
            50000,
            51500,
            0.1,
            100000
        );

        $this->assertEquals('HIGH', $driftResult['risk_level']); // %3 >= %3 threshold
    }

    public function test_update_config_partial(): void
    {
        $newConfig = [
            'maxDriftThreshold' => 4.0, // Sadece drift threshold
        ];

        $result = $this->service->updateConfig($newConfig);

        $this->assertTrue($result['ok']);
        $this->assertEquals(4.0, $result['updated_config']['maxDriftThreshold']);
        // Diğer değerler değişmemeli
        $this->assertEquals(20.0, $result['updated_config']['maxPositionSizeThreshold']);
        $this->assertEquals(300, $result['updated_config']['driftCheckInterval']);
    }

    public function test_update_config_invalid_keys(): void
    {
        $newConfig = [
            'invalidKey' => 'value',
            'maxDriftThreshold' => 0.04,
        ];

        $result = $this->service->updateConfig($newConfig);

        $this->assertTrue($result['ok']);
        $this->assertEquals(0.04, $result['updated_config']['maxDriftThreshold']);
        // Invalid key ignored
    }

    public function test_drift_calculation_precision(): void
    {
        $symbol = 'BTCUSDT';
        $side = 'LONG';
        $entryPrice = 50000;
        $currentPrice = 50001; // Çok küçük fark
        $qty = 0.1; // Küçük pozisyon (5% of equity)
        $equity = 100000;

        $result = $this->service->checkPositionDrift($symbol, $side, $entryPrice, $currentPrice, $qty, $equity);

        $this->assertTrue($result['ok']);
        $this->assertEquals(0.002, $result['drift_pct'], '', 0.001); // %0.002
        $this->assertEquals('LOW', $result['risk_level']); // %0.002 < %5 threshold
    }

    public function test_position_size_calculation_precision(): void
    {
        $symbol = 'BTCUSDT';
        $side = 'LONG';
        $entryPrice = 50000;
        $currentPrice = 50000;
        $qty = 0.12345678; // Çok hassas miktar
        $equity = 100000;

        $result = $this->service->checkPositionDrift($symbol, $side, $entryPrice, $currentPrice, $qty, $equity);

        $this->assertTrue($result['ok']);
        $this->assertEquals(6.172839, $result['position_size_pct'], '', 0.00000001); // 6172.839/100000
        $this->assertEquals('LOW', $result['risk_level']);
    }
}
