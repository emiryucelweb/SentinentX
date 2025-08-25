<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\Trading\PositionSizer;
use App\Services\Trading\StopCalculator;
use Tests\TestCase;

class ComprehensiveMathTest extends TestCase
{
    /** @test */
    public function test_position_sizing_basic_functionality(): void
    {
        $sizer = new PositionSizer;

        $result = $sizer->sizeByRisk('LONG', 50000, 49000, 10000, 10, 0.01, 0.001, 0.001);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('qty', $result);
        $this->assertArrayHasKey('leverage', $result);
        $this->assertGreaterThan(0, $result['qty']);
    }

    /** @test */
    public function test_stop_calculator_basic_functionality(): void
    {
        $stopCalc = new StopCalculator;

        $result = $stopCalc->compute('BTCUSDT', 'LONG', 50000, 1.5);

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertIsFloat($result[0]); // Stop loss
        $this->assertIsFloat($result[1]); // Take profit
    }

    /** @test */
    public function test_bcmath_precision(): void
    {
        // Test BCMath usage for financial calculations
        if (extension_loaded('bcmath')) {
            $price1 = \bcadd('0.1', '0.2', 8);
            $price2 = '0.30000000';

            $this->assertEquals($price2, $price1, 'BCMath should provide exact precision');
        } else {
            $this->markTestSkipped('BCMath extension not loaded');
        }

        // Float precision issue
        $floatSum = 0.1 + 0.2;
        $this->assertNotEquals(0.3, $floatSum, 'Float should have precision issues');
    }

    /** @test */
    public function test_zero_division_protection(): void
    {
        $sizer = new PositionSizer;

        // Zero risk scenario (entry = stop)
        $result = $sizer->sizeByRisk('LONG', 50000, 50000, 10000, 10, 0.01, 0.001, 0.001);
        $this->assertGreaterThanOrEqual(0.001, $result['qty'], 'Should handle zero risk gracefully');

        // Zero equity scenario
        $result2 = $sizer->sizeByRisk('LONG', 50000, 49000, 0, 10, 0.01, 0.001, 0.001);
        $this->assertGreaterThanOrEqual(0.0, $result2['qty'], 'Should handle zero equity gracefully');
    }

    /** @test */
    public function test_overflow_protection(): void
    {
        $sizer = new PositionSizer;

        // Very large equity test
        $result = $sizer->sizeByRisk('LONG', 50000, 49000, 1e12, 10, 0.01, 0.001, 0.001);

        $this->assertLessThan(PHP_FLOAT_MAX, $result['qty'], 'Should prevent overflow');
        $this->assertGreaterThan(0.0, $result['qty'], 'Should return valid qty');
    }

    /** @test */
    public function test_negative_value_protection(): void
    {
        $sizer = new PositionSizer;

        // Negative entry/stop scenario
        $result = $sizer->sizeByRisk('LONG', -50000, -49000, 10000, 10, 0.01, 0.001, 0.001);

        $this->assertGreaterThanOrEqual(0.001, $result['qty'], 'Should handle negative values');
    }

    /** @test */
    public function test_risk_calculation_consistency(): void
    {
        $sizer = new PositionSizer;

        $risks = [0.01, 0.02, 0.03, 0.04, 0.05];
        $results = [];

        foreach ($risks as $risk) {
            $results[$risk] = $sizer->sizeByRisk('LONG', 50000, 49000, 10000, 10, $risk, 0.001, 0.001);
        }

        // Higher risk should result in higher qty (or same due to min/max constraints)
        for ($i = 1; $i < count($risks); $i++) {
            $prevRisk = $risks[$i - 1];
            $currRisk = $risks[$i];

            $this->assertGreaterThanOrEqual(
                $results[$prevRisk]['qty'],
                $results[$currRisk]['qty'],
                "Higher risk ({$currRisk}) should have >= qty than lower risk ({$prevRisk})"
            );
        }
    }

    /** @test */
    public function test_leverage_constraints(): void
    {
        $sizer = new PositionSizer;

        // Test various leverage levels
        $leverages = [1, 10, 50, 100, 1000];

        foreach ($leverages as $leverage) {
            $result = $sizer->sizeByRisk('LONG', 50000, 49000, 10000, $leverage, 0.01, 0.001, 0.001);

            $this->assertEquals($leverage, $result['leverage'], "Leverage should be preserved: {$leverage}");
            $this->assertGreaterThan(0, $result['qty'], "Should return positive qty for leverage: {$leverage}");
        }
    }
}
