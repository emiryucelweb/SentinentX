<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\Risk\RiskGuard;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[CoversClass(RiskGuard::class)]
#[Group('unit')]
#[Group('risk')]
final class RiskGuardTest extends TestCase
{
    private RiskGuard $riskGuard;

    protected function setUp(): void
    {
        parent::setUp();
        $this->riskGuard = new RiskGuard;
    }

    #[Test]
    public function daily_loss_breached_returns_true_when_loss_exceeds_threshold(): void
    {
        $todayPnlPct = -5.0;
        $dailyMaxLossPct = 3.0;

        $result = $this->riskGuard->dailyLossBreached($todayPnlPct, $dailyMaxLossPct);

        $this->assertTrue($result);
    }

    #[Test]
    public function daily_loss_breached_returns_false_when_loss_below_threshold(): void
    {
        $todayPnlPct = -2.0;
        $dailyMaxLossPct = 3.0;

        $result = $this->riskGuard->dailyLossBreached($todayPnlPct, $dailyMaxLossPct);

        $this->assertFalse($result);
    }

    #[Test]
    public function daily_loss_breached_returns_true_when_loss_equals_threshold(): void
    {
        $todayPnlPct = -3.0;
        $dailyMaxLossPct = 3.0;

        $result = $this->riskGuard->dailyLossBreached($todayPnlPct, $dailyMaxLossPct);

        $this->assertTrue($result);
    }

    #[Test]
    public function daily_loss_breached_returns_false_when_profit(): void
    {
        $todayPnlPct = 2.0;
        $dailyMaxLossPct = 3.0;

        $result = $this->riskGuard->dailyLossBreached($todayPnlPct, $dailyMaxLossPct);

        $this->assertFalse($result);
    }

    #[Test]
    public function daily_loss_breached_returns_false_when_break_even(): void
    {
        $todayPnlPct = 0.0;
        $dailyMaxLossPct = 3.0;

        $result = $this->riskGuard->dailyLossBreached($todayPnlPct, $dailyMaxLossPct);

        $this->assertFalse($result);
    }

    #[Test]
    public function daily_loss_breached_handles_negative_threshold(): void
    {
        $todayPnlPct = -5.0;
        $dailyMaxLossPct = -3.0; // Negative threshold

        $result = $this->riskGuard->dailyLossBreached($todayPnlPct, $dailyMaxLossPct);

        $this->assertTrue($result);
    }

    #[Test]
    public function daily_loss_breached_handles_zero_threshold(): void
    {
        $todayPnlPct = -1.0;
        $dailyMaxLossPct = 0.0;

        $result = $this->riskGuard->dailyLossBreached($todayPnlPct, $dailyMaxLossPct);

        $this->assertTrue($result);
    }

    #[Test]
    public function daily_loss_breached_handles_large_loss(): void
    {
        $todayPnlPct = -50.0;
        $dailyMaxLossPct = 10.0;

        $result = $this->riskGuard->dailyLossBreached($todayPnlPct, $dailyMaxLossPct);

        $this->assertTrue($result);
    }

    #[Test]
    public function daily_loss_breached_handles_small_loss(): void
    {
        $todayPnlPct = -0.1;
        $dailyMaxLossPct = 1.0;

        $result = $this->riskGuard->dailyLossBreached($todayPnlPct, $dailyMaxLossPct);

        $this->assertFalse($result);
    }

    #[Test]
    public function daily_loss_breached_handles_precise_values(): void
    {
        $todayPnlPct = -3.14159;
        $dailyMaxLossPct = 3.14159;

        $result = $this->riskGuard->dailyLossBreached($todayPnlPct, $dailyMaxLossPct);

        $this->assertTrue($result);
    }

    #[Test]
    public function usdt_depeg_returns_true_when_below_lower_bound(): void
    {
        $usdtUsd = 0.98;
        $lowerBound = 0.99;
        $upperBound = 1.01;

        $result = $this->riskGuard->usdtDepeg($usdtUsd, $lowerBound, $upperBound);

        $this->assertTrue($result);
    }

    #[Test]
    public function usdt_depeg_returns_true_when_above_upper_bound(): void
    {
        $usdtUsd = 1.02;
        $lowerBound = 0.99;
        $upperBound = 1.01;

        $result = $this->riskGuard->usdtDepeg($usdtUsd, $lowerBound, $upperBound);

        $this->assertTrue($result);
    }

    #[Test]
    public function usdt_depeg_returns_false_when_within_bounds(): void
    {
        $usdtUsd = 1.0;
        $lowerBound = 0.99;
        $upperBound = 1.01;

        $result = $this->riskGuard->usdtDepeg($usdtUsd, $lowerBound, $upperBound);

        $this->assertFalse($result);
    }

    #[Test]
    public function usdt_depeg_returns_false_when_at_lower_bound(): void
    {
        $usdtUsd = 0.99;
        $lowerBound = 0.99;
        $upperBound = 1.01;

        $result = $this->riskGuard->usdtDepeg($usdtUsd, $lowerBound, $upperBound);

        $this->assertFalse($result);
    }

    #[Test]
    public function usdt_depeg_returns_false_when_at_upper_bound(): void
    {
        $usdtUsd = 1.01;
        $lowerBound = 0.99;
        $upperBound = 1.01;

        $result = $this->riskGuard->usdtDepeg($usdtUsd, $lowerBound, $upperBound);

        $this->assertFalse($result);
    }

    #[Test]
    public function usdt_depeg_throws_exception_when_usdt_usd_is_zero(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('usdtUsd>0');

        $this->riskGuard->usdtDepeg(0.0, 0.99, 1.01);
    }

    #[Test]
    public function usdt_depeg_throws_exception_when_usdt_usd_is_negative(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('usdtUsd>0');

        $this->riskGuard->usdtDepeg(-1.0, 0.99, 1.01);
    }

    #[Test]
    public function usdt_depeg_handles_very_small_values(): void
    {
        $usdtUsd = 0.000001;
        $lowerBound = 0.000001;
        $upperBound = 1.0;

        $result = $this->riskGuard->usdtDepeg($usdtUsd, $lowerBound, $upperBound);

        $this->assertFalse($result);
    }

    #[Test]
    public function usdt_depeg_handles_very_large_values(): void
    {
        $usdtUsd = 1000000.0;
        $lowerBound = 0.99;
        $upperBound = 1000000.0;

        $result = $this->riskGuard->usdtDepeg($usdtUsd, $lowerBound, $upperBound);

        $this->assertFalse($result);
    }

    #[Test]
    public function usdt_depeg_handles_precise_bounds(): void
    {
        $usdtUsd = 1.0;
        $lowerBound = 0.999999;
        $upperBound = 1.000001;

        $result = $this->riskGuard->usdtDepeg($usdtUsd, $lowerBound, $upperBound);

        $this->assertFalse($result);
    }

    #[Test]
    public function usdt_depeg_handles_negative_bounds(): void
    {
        $usdtUsd = 1.0;
        $lowerBound = -1.0;
        $upperBound = 2.0;

        $result = $this->riskGuard->usdtDepeg($usdtUsd, $lowerBound, $upperBound);

        $this->assertFalse($result);
    }

    #[Test]
    public function usdt_depeg_handles_reversed_bounds(): void
    {
        $usdtUsd = 1.0;
        $lowerBound = 1.01; // Higher than upper
        $upperBound = 0.99; // Lower than lower

        $result = $this->riskGuard->usdtDepeg($usdtUsd, $lowerBound, $upperBound);

        $this->assertTrue($result); // Should trigger because 1.0 < 1.01
    }

    #[Test]
    public function usdt_depeg_handles_equal_bounds(): void
    {
        $usdtUsd = 1.0;
        $bound = 1.0;

        $result = $this->riskGuard->usdtDepeg($usdtUsd, $bound, $bound);

        $this->assertFalse($result);
    }

    #[Test]
    public function usdt_depeg_handles_extreme_depeg_scenarios(): void
    {
        $usdtUsd = 0.5; // 50% depeg
        $lowerBound = 0.99;
        $upperBound = 1.01;

        $result = $this->riskGuard->usdtDepeg($usdtUsd, $lowerBound, $upperBound);

        $this->assertTrue($result);
    }

    #[Test]
    public function usdt_depeg_handles_minimal_depeg_scenarios(): void
    {
        $usdtUsd = 0.989; // Just below 0.99
        $lowerBound = 0.99;
        $upperBound = 1.01;

        $result = $this->riskGuard->usdtDepeg($usdtUsd, $lowerBound, $upperBound);

        $this->assertTrue($result);
    }
}
