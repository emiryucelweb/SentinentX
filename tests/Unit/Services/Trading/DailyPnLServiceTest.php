<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Trading;

use App\Services\Trading\DailyPnLService;
use App\Models\User;
use App\Models\Trade;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use Tests\TestCase;

class DailyPnLServiceTest extends TestCase
{
    use RefreshDatabase;

    private DailyPnLService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new DailyPnLService();
        Cache::flush();
    }

    public function test_calculates_daily_pnl_for_completed_trades(): void
    {
        $user = User::factory()->create([
            'meta' => ['risk_profile' => 'moderate']
        ]);

        $today = now()->startOfDay();

        // Create completed trades for today
        Trade::factory()->create([
            'user_id' => $user->id,
            'status' => 'CLOSED',
            'pnl' => 150.50,
            'fees_paid' => 5.00,
            'closed_at' => $today->copy()->addHours(10),
        ]);

        Trade::factory()->create([
            'user_id' => $user->id,
            'status' => 'CLOSED',
            'pnl' => -75.25,
            'fees_paid' => 3.50,
            'closed_at' => $today->copy()->addHours(14),
        ]);

        $result = $this->service->calculateDailyPnL($user);

        $this->assertEquals($today->format('Y-m-d'), $result['date']);
        $this->assertEquals(75.25, $result['pnl_breakdown']['completed_trades']['total']); // 150.50 - 75.25
        $this->assertEquals(8.50, $result['pnl_breakdown']['total_fees']); // 5.00 + 3.50
        $this->assertEquals(66.75, $result['pnl_breakdown']['net_pnl']); // 75.25 - 8.50
        $this->assertEquals(2, $result['trade_counts']['completed']);
    }

    public function test_calculates_unrealized_pnl_for_open_positions(): void
    {
        $user = User::factory()->create();

        $today = now()->startOfDay();

        // Create open trade
        Trade::factory()->create([
            'user_id' => $user->id,
            'status' => 'OPEN',
            'symbol' => 'BTCUSDT',
            'side' => 'LONG',
            'entry_price' => 43000.00,
            'qty' => 0.1,
            'fees_paid' => 2.00,
            'created_at' => $today->copy()->addHours(8),
        ]);

        $result = $this->service->calculateDailyPnL($user);

        // Should include unrealized PnL (mocked current price: 43250)
        // Unrealized PnL = (43250 - 43000) * 0.1 = 25.0
        $this->assertGreaterThan(0, $result['pnl_breakdown']['unrealized_pnl']['total']);
        $this->assertEquals(1, $result['trade_counts']['open']);
    }

    public function test_handles_carry_over_positions(): void
    {
        $user = User::factory()->create();

        $yesterday = now()->subDay()->startOfDay();

        // Create position opened yesterday but still open
        Trade::factory()->create([
            'user_id' => $user->id,
            'status' => 'OPEN',
            'symbol' => 'ETHUSDT',
            'side' => 'SHORT',
            'entry_price' => 2700.00,
            'qty' => 1.0,
            'created_at' => $yesterday,
        ]);

        $result = $this->service->calculateDailyPnL($user);

        $this->assertEquals(1, $result['trade_counts']['carry_over']);
        $this->assertArrayHasKey('unrealized_pnl', $result['pnl_breakdown']);
    }

    public function test_compares_against_daily_target(): void
    {
        $user = User::factory()->create([
            'meta' => ['risk_profile' => 'conservative'] // 20% daily target
        ]);

        $today = now()->startOfDay();

        // Create profitable trade that exceeds target
        Trade::factory()->create([
            'user_id' => $user->id,
            'status' => 'CLOSED',
            'pnl' => 250.00, // Above 20% target
            'fees_paid' => 5.00,
            'closed_at' => $today->copy()->addHours(12),
        ]);

        $result = $this->service->calculateDailyPnL($user);

        $this->assertEquals(20.0, $result['daily_target_pct']);
        $this->assertTrue($result['target_analysis']['target_reached']);
        $this->assertGreaterThan(100, $result['target_analysis']['target_progress_pct']);
    }

    public function test_get_daily_pnl_for_ai_context(): void
    {
        $user = User::factory()->create([
            'meta' => ['risk_profile' => 'moderate'] // 50% daily target
        ]);

        $today = now()->startOfDay();

        // Create moderate profit
        Trade::factory()->create([
            'user_id' => $user->id,
            'status' => 'CLOSED',
            'pnl' => 30.00,
            'fees_paid' => 2.00,
            'closed_at' => $today->copy()->addHours(10),
        ]);

        $aiContext = $this->service->getDailyPnLForAI($user);

        $this->assertEquals(28.00, $aiContext['daily_pnl']); // 30 - 2 fees
        $this->assertEquals(56.0, $aiContext['daily_pnl_percentage']); // 28/50 * 100
        $this->assertFalse($aiContext['target_reached']);
        $this->assertTrue($aiContext['should_continue_trading']);
        $this->assertEquals('on_track', $aiContext['risk_status']);
        $this->assertStringContains('Good progress', $aiContext['recommendation']);
    }

    public function test_risk_status_assessment(): void
    {
        $user = User::factory()->create([
            'meta' => ['risk_profile' => 'aggressive'] // 150% daily target
        ]);

        // Test target reached status
        Trade::factory()->create([
            'user_id' => $user->id,
            'status' => 'CLOSED',
            'pnl' => 160.00, // Above 150% target
            'fees_paid' => 5.00,
            'closed_at' => now()->startOfDay()->addHours(10),
        ]);

        $aiContext = $this->service->getDailyPnLForAI($user);

        $this->assertEquals('target_reached', $aiContext['risk_status']);
        $this->assertFalse($aiContext['should_continue_trading']);
        $this->assertStringContains('target achieved', $aiContext['recommendation']);
    }

    public function test_handles_significant_loss(): void
    {
        $user = User::factory()->create();

        Trade::factory()->create([
            'user_id' => $user->id,
            'status' => 'CLOSED',
            'pnl' => -75.00, // Significant loss
            'fees_paid' => 5.00,
            'closed_at' => now()->startOfDay()->addHours(10),
        ]);

        $aiContext = $this->service->getDailyPnLForAI($user);

        $this->assertEquals('significant_loss', $aiContext['risk_status']);
        $this->assertFalse($aiContext['should_continue_trading']);
        $this->assertStringContains('Significant loss', $aiContext['recommendation']);
    }

    public function test_weekly_pnl_summary(): void
    {
        $user = User::factory()->create();

        // Create trades for different days
        $startOfWeek = now()->startOfWeek();
        
        for ($i = 0; $i < 3; $i++) {
            Trade::factory()->create([
                'user_id' => $user->id,
                'status' => 'CLOSED',
                'pnl' => 50.00 * ($i + 1),
                'fees_paid' => 2.00,
                'closed_at' => $startOfWeek->copy()->addDays($i)->addHours(12),
            ]);
        }

        $summary = $this->service->getWeeklyPnLSummary($user);

        $this->assertEquals($startOfWeek->format('Y-m-d'), $summary['week_start']);
        $this->assertCount(7, $summary['daily_breakdown']); // 7 days
        $this->assertEquals($user->id, $summary['user_id']);
    }

    public function test_caches_results_for_performance(): void
    {
        $user = User::factory()->create();

        Trade::factory()->create([
            'user_id' => $user->id,
            'status' => 'CLOSED',
            'pnl' => 100.00,
            'closed_at' => now()->startOfDay()->addHours(10),
        ]);

        // First call
        $result1 = $this->service->calculateDailyPnL($user);
        
        // Second call should use cache
        $result2 = $this->service->calculateDailyPnL($user);

        $this->assertEquals($result1, $result2);
        $this->assertArrayHasKey('calculated_at', $result1);
    }

    public function test_handles_different_risk_profiles(): void
    {
        $profiles = ['conservative', 'moderate', 'aggressive'];
        $expectedTargets = [20.0, 50.0, 150.0];

        foreach ($profiles as $index => $profile) {
            $user = User::factory()->create([
                'meta' => ['risk_profile' => $profile]
            ]);

            $result = $this->service->calculateDailyPnL($user);

            $this->assertEquals($expectedTargets[$index], $result['daily_target_pct']);
        }
    }
}
