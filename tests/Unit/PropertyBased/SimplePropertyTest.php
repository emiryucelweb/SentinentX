<?php

declare(strict_types=1);

namespace Tests\Unit\PropertyBased;

use App\Services\Trading\PositionSizer;
use Eris\Generators;
use Eris\TestTrait;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Simple property-based tests using basic Eris generators
 */
class SimplePropertyTest extends TestCase
{
    use TestTrait;

    #[Test]
    public function position_sizer_basic_invariants(): void
    {
        $this->forAll(
            Generators::choose(1000, 50000),   // equity
            Generators::choose(2, 20),         // leverage
            Generators::choose(10000, 80000)   // price
        )
            ->then(function ($equity, $leverage, $price) {
                $sizer = new PositionSizer;

                // Test basic quantity calculation
                $result = $sizer->sizeByRisk(
                    'LONG',
                    $price,
                    $price * 0.95, // 5% stop loss
                    $equity,
                    $leverage,
                    0.02, // 2% risk
                    0.001,
                    0.001
                );

                // Invariant 1: Quantity should never be negative
                $this->assertGreaterThanOrEqual(0, $result['qty']);

                // Invariant 2: Result should have required structure
                $this->assertArrayHasKey('qty', $result);
                $this->assertArrayHasKey('leverage', $result);

                // Invariant 3: Leverage should be preserved
                $this->assertEquals($leverage, $result['leverage']);

                // Invariant 4: Quantity should be a valid number
                $this->assertIsFloat($result['qty']);
                $this->assertFinite($result['qty']);
            });
    }

    #[Test]
    public function im_cap_basic_invariants(): void
    {
        $this->forAll(
            Generators::choose(5000, 100000),  // equity
            Generators::choose(10, 90),        // margin utilization (as %)
            Generators::choose(2, 25),         // leverage
            Generators::choose(20000, 80000)   // price
        )
            ->then(function ($equity, $marginUtil, $leverage, $price) {
                $sizer = new PositionSizer;

                $marginUtilFloat = $marginUtil / 100.0; // Convert to decimal
                $freeCollateral = $equity * (1 - $marginUtilFloat);

                $result = $sizer->sizeByImCap(
                    $equity,
                    $marginUtilFloat,
                    $freeCollateral,
                    $leverage,
                    $price,
                    0.001,
                    0.001
                );

                // Invariant 1: Quantity should never be negative
                $this->assertGreaterThanOrEqual(0, $result['qty']);

                // Invariant 2: Result should have required structure
                $this->assertArrayHasKey('qty', $result);
                $this->assertArrayHasKey('leverage', $result);
                $this->assertArrayHasKey('risk_band', $result);
                $this->assertArrayHasKey('im_required', $result);

                // Invariant 3: Risk band should be valid
                $validBands = ['low', 'medium', 'high', 'extreme'];
                $this->assertContains($result['risk_band'], $validBands);

                // Invariant 4: IM required should not be negative
                $this->assertGreaterThanOrEqual(0, $result['im_required']);
            });
    }

    #[Test]
    public function risk_percentage_invariants(): void
    {
        $this->forAll(
            Generators::choose(10000, 50000),  // equity
            Generators::choose(1, 10),         // risk percentage (1-10%)
            Generators::choose(30000, 70000)   // price
        )
            ->then(function ($equity, $riskPct, $price) {
                $sizer = new PositionSizer;

                $riskFloat = $riskPct / 100.0; // Convert to decimal
                $stopLoss = $price * 0.95; // 5% stop loss

                $result = $sizer->sizeByRisk(
                    'LONG',
                    $price,
                    $stopLoss,
                    $equity,
                    10, // Fixed leverage
                    $riskFloat,
                    0.001,
                    0.001
                );

                // Invariant 1: Risk should be proportional to equity
                if (isset($result['risk_amount']) && $result['risk_amount'] > 0) {
                    $maxExpectedRisk = $equity * $riskFloat * 1.2; // 20% tolerance
                    $this->assertLessThanOrEqual($maxExpectedRisk, $result['risk_amount']);
                }

                // Invariant 2: Higher risk percentage should generally yield higher quantities
                // (simplified test - actual behavior depends on many factors)
                $this->assertGreaterThanOrEqual(0, $result['qty']);
            });
    }

    #[Test]
    public function leverage_constraints(): void
    {
        $this->forAll(
            Generators::choose(1, 100),        // leverage
            Generators::choose(5000, 50000),   // equity
            Generators::choose(20000, 80000)   // price
        )
            ->then(function ($leverage, $equity, $price) {
                $sizer = new PositionSizer;

                $result = $sizer->sizeByRisk(
                    'LONG',
                    $price,
                    $price * 0.9, // 10% stop loss
                    $equity,
                    $leverage,
                    0.02, // 2% risk
                    0.001,
                    0.001
                );

                // Invariant 1: Leverage should be preserved in result
                $this->assertEquals($leverage, $result['leverage']);

                // Invariant 2: Result should be valid regardless of leverage
                $this->assertIsArray($result);
                $this->assertArrayHasKey('qty', $result);
                $this->assertIsFloat($result['qty']);
            });
    }
}
