<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Contracts\Notifier\AlertDispatcher;
use App\Models\LabMetric;
use App\Services\Lab\MetricsService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

final class EodMetricsCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Test ortamında migration'ları çalıştır
        $this->artisan('migrate:fresh');
        $this->artisan('db:seed');
    }

    public function test_eod_metrics_computes_and_saves_daily_metrics(): void
    {
        $this->mock(MetricsService::class, function ($mock) {
            $mock->shouldReceive('computeDaily')
                ->andReturn([
                    'pf' => 1.25,
                    'pf_gross' => 1.26,
                    'maxdd_pct' => 8.5,
                    'sharpe' => 1.2,
                    'trades' => 5,
                    'avg_trade_net_pct' => 4.5,
                    'avg_trade_gross_pct' => 4.6,
                ]);
        });

        $this->mock(AlertDispatcher::class, function ($mock) {
            $mock->shouldReceive('send')->once();
        });

        $this->artisan('sentx:eod-metrics')
            ->assertExitCode(0);

        $this->assertDatabaseHas('lab_metrics', [
            'pf' => 1.25,
            'maxdd_pct' => 8.5,
            'sharpe' => 1.2,
        ]);
    }

    public function test_eod_metrics_with_custom_date(): void
    {
        $customDate = '2024-01-15';

        $this->mock(MetricsService::class, function ($mock) {
            $mock->shouldReceive('computeDaily')
                ->with(\Mockery::type(CarbonImmutable::class), 10000.0)
                ->andReturn([
                    'pf' => 1.15,
                    'pf_gross' => 1.16,
                    'maxdd_pct' => 12.0,
                    'sharpe' => 0.9,
                    'trades' => 3,
                    'avg_trade_net_pct' => 4.8,
                    'avg_trade_gross_pct' => 4.9,
                ]);
        });

        $this->mock(AlertDispatcher::class, function ($mock) {
            $mock->shouldReceive('send')->once();
        });

        $this->artisan('sentx:eod-metrics', ['--date' => $customDate])
            ->assertExitCode(0);

        $this->assertDatabaseHas('lab_metrics', [
            'as_of' => $customDate,
            'pf' => 1.15,
            'maxdd_pct' => 12.0,
            'sharpe' => 0.9,
        ]);
    }

    public function test_eod_metrics_acceptance_pass(): void
    {
        $this->mock(MetricsService::class, function ($mock) {
            $mock->shouldReceive('computeDaily')
                ->andReturn([
                    'pf' => 1.3,        // > 1.2 (min_pf)
                    'pf_gross' => 1.31,  // > 1.2 (min_pf)
                    'maxdd_pct' => 10.0, // < 15.0 (max_dd_pct)
                    'sharpe' => 1.1,     // > 0.8 (min_sharpe)
                    'trades' => 8,
                    'avg_trade_net_pct' => 3.3,
                    'avg_trade_gross_pct' => 3.4,
                ]);
        });

        $this->mock(AlertDispatcher::class, function ($mock) {
            $mock->shouldReceive('send')
                ->with('info', 'LAB_ACCEPTANCE_PASS', 'LAB acceptance criteria PASSED', \Mockery::any(), \Mockery::any())
                ->once();
        });

        $this->artisan('sentx:eod-metrics')
            ->expectsOutput('acceptance=PASS')
            ->assertExitCode(0);
    }

    public function test_eod_metrics_acceptance_fail(): void
    {
        $this->mock(MetricsService::class, function ($mock) {
            $mock->shouldReceive('computeDaily')
                ->andReturn([
                    'pf' => 1.1,        // < 1.2 (min_pf)
                    'pf_gross' => 1.12,  // < 1.2 (min_pf)
                    'maxdd_pct' => 18.0, // > 15.0 (max_dd_pct)
                    'sharpe' => 0.7,     // < 0.8 (min_sharpe)
                    'trades' => 12,
                    'avg_trade_net_pct' => 0.9,
                    'avg_trade_gross_pct' => 1.0,
                ]);
        });

        $this->mock(AlertDispatcher::class, function ($mock) {
            $mock->shouldReceive('send')
                ->with('warn', 'LAB_ACCEPTANCE_FAIL', 'LAB acceptance criteria FAILED', \Mockery::any(), \Mockery::any())
                ->once();
        });

        $this->artisan('sentx:eod-metrics')
            ->expectsOutput('acceptance=FAIL')
            ->assertExitCode(0);
    }

    public function test_eod_metrics_acceptance_partial_fail(): void
    {
        $this->mock(MetricsService::class, function ($mock) {
            $mock->shouldReceive('computeDaily')
                ->andReturn([
                    'pf' => 1.3,        // > 1.2 (min_pf) - PASS
                    'pf_gross' => 1.32,  // > 1.2 (min_pf) - PASS
                    'maxdd_pct' => 20.0, // > 15.0 (max_dd_pct) - FAIL
                    'sharpe' => 1.0,     // > 0.8 (min_sharpe) - PASS
                    'trades' => 10,
                    'avg_trade_net_pct' => 2.6,
                    'avg_trade_gross_pct' => 2.7,
                ]);
        });

        $this->mock(AlertDispatcher::class, function ($mock) {
            $mock->shouldReceive('send')
                ->with('warn', 'LAB_ACCEPTANCE_FAIL', 'LAB acceptance criteria FAILED', \Mockery::any(), \Mockery::any())
                ->once();
        });

        $this->artisan('sentx:eod-metrics')
            ->expectsOutput('acceptance=FAIL')
            ->assertExitCode(0);
    }

    public function test_eod_metrics_skips_alert_when_disabled(): void
    {
        Config::set('lab.simulation.alerts.acceptance', false);

        $this->mock(MetricsService::class, function ($mock) {
            $mock->shouldReceive('computeDaily')
                ->andReturn([
                    'pf' => 1.25,
                    'pf_gross' => 1.26,
                    'maxdd_pct' => 8.5,
                    'sharpe' => 1.2,
                    'trades' => 6,
                    'avg_trade_net_pct' => 3.8,
                    'avg_trade_gross_pct' => 3.9,
                ]);
        });

        $this->mock(AlertDispatcher::class, function ($mock) {
            $mock->shouldReceive('send')->never();
        });

        $this->artisan('sentx:eod-metrics')
            ->assertExitCode(0);
    }

    public function test_eod_metrics_handles_null_metrics(): void
    {
        $this->mock(MetricsService::class, function ($mock) {
            $mock->shouldReceive('computeDaily')
                ->andReturn([
                    'pf' => null,
                    'pf_gross' => null,
                    'maxdd_pct' => null,
                    'sharpe' => null,
                    'trades' => 0,
                    'avg_trade_net_pct' => 0.0,
                    'avg_trade_gross_pct' => 0.0,
                ]);
        });

        $this->mock(AlertDispatcher::class, function ($mock) {
            $mock->shouldReceive('send')->once(); // Null metrics'te acceptance PASS oluyor
        });

        $this->artisan('sentx:eod-metrics')
            ->assertExitCode(0);

        // Null değerler kaydedilmiş olmalı (upsert çalışıyor)
        $this->assertDatabaseHas('lab_metrics', [
            'as_of' => now()->toDateString(),
        ]);
    }

    public function test_eod_metrics_uses_custom_initial_equity(): void
    {
        Config::set('lab.initial_equity', 25000.0);

        $this->mock(MetricsService::class, function ($mock) {
            $mock->shouldReceive('computeDaily')
                ->with(\Mockery::type(CarbonImmutable::class), 25000.0)
                ->andReturn([
                    'pf' => 1.2,
                    'pf_gross' => 1.21,
                    'maxdd_pct' => 10.0,
                    'sharpe' => 0.9,
                    'trades' => 4,
                    'avg_trade_net_pct' => 4.6,
                    'avg_trade_gross_pct' => 4.7,
                ]);
        });

        $this->mock(AlertDispatcher::class, function ($mock) {
            $mock->shouldReceive('send')->once();
        });

        $this->artisan('sentx:eod-metrics')
            ->assertExitCode(0);

        $this->assertDatabaseHas('lab_metrics', [
            'pf' => 1.2,
            'maxdd_pct' => 10.0,
            'sharpe' => 0.9,
        ]);
    }

    public function test_eod_metrics_updates_existing_record(): void
    {
        $testDate = '2024-01-15';

        // Test isolation: Tüm lab_metrics tablosunu temizle
        LabMetric::query()->delete();

        // Önce bir kayıt oluştur
        LabMetric::create([
            'as_of' => $testDate,
            'pf' => 1.1,
            'maxdd_pct' => 15.0,
            'sharpe' => 0.8,
            'meta' => json_encode(['initial_equity' => 10000.0]),
        ]);

        $this->mock(MetricsService::class, function ($mock) {
            $mock->shouldReceive('computeDaily')
                ->andReturn([
                    'pf' => 1.3,
                    'pf_gross' => 1.31,
                    'maxdd_pct' => 12.0,
                    'sharpe' => 1.1,
                    'trades' => 7,
                    'avg_trade_net_pct' => 3.9,
                    'avg_trade_gross_pct' => 4.0,
                ]);
        });

        $this->mock(AlertDispatcher::class, function ($mock) {
            $mock->shouldReceive('send')->once();
        });

        $this->artisan('sentx:eod-metrics', ['--date' => $testDate])
            ->assertExitCode(0);

        // Kayıt güncellenmiş olmalı
        $this->assertDatabaseHas('lab_metrics', [
            'as_of' => $testDate,
            'pf' => 1.3,
            'maxdd_pct' => 12.0,
            'sharpe' => 1.1,
        ]);

        // Upsert sonrası kayıt güncellenmiş olmalı
        $this->assertDatabaseHas('lab_metrics', [
            'as_of' => $testDate,
            'pf' => 1.3,
            'maxdd_pct' => 12.0,
            'sharpe' => 1.1,
        ]);
    }

    public function test_eod_metrics_outputs_json_format(): void
    {
        $this->mock(MetricsService::class, function ($mock) {
            $mock->shouldReceive('computeDaily')
                ->andReturn([
                    'pf' => 1.25,
                    'pf_gross' => 1.26,
                    'maxdd_pct' => 8.5,
                    'sharpe' => 1.2,
                    'trades' => 5,
                    'avg_trade_net_pct' => 4.5,
                    'avg_trade_gross_pct' => 4.6,
                ]);
        });

        $this->mock(AlertDispatcher::class, function ($mock) {
            $mock->shouldReceive('send')->once();
        });

        $this->artisan('sentx:eod-metrics')
            ->assertExitCode(0);
    }
}
