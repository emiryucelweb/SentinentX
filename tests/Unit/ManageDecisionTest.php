<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\DTO\ManageDecision;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class ManageDecisionTest extends TestCase
{
    #[Test]
    public function test_constructor_with_valid_parameters(): void
    {
        $decision = new ManageDecision('HOLD', 75, 45000.0, 52000.0, 0.5, 'Market conditions stable');

        $this->assertSame('HOLD', $decision->action);
        $this->assertSame(75, $decision->confidence);
        $this->assertSame(45000.0, $decision->newStopLoss);
        $this->assertSame(52000.0, $decision->newTakeProfit);
        $this->assertSame(0.5, $decision->qtyDeltaFactor);
        $this->assertSame('Market conditions stable', $decision->reason);
    }

    #[Test]
    public function test_constructor_action_uppercase_conversion(): void
    {
        $decision = new ManageDecision('hold', 80);

        $this->assertSame('HOLD', $decision->action);
    }

    #[Test]
    public function test_constructor_with_minimal_parameters(): void
    {
        $decision = new ManageDecision('CLOSE', 90);

        $this->assertSame('CLOSE', $decision->action);
        $this->assertSame(90, $decision->confidence);
        $this->assertNull($decision->newStopLoss);
        $this->assertNull($decision->newTakeProfit);
        $this->assertNull($decision->qtyDeltaFactor);
        $this->assertSame('', $decision->reason);
    }

    #[Test]
    public function test_constructor_invalid_action_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('ManageDecision action HOLD|CLOSE');

        new ManageDecision('INVALID', 50);
    }

    #[Test]
    public function test_constructor_confidence_below_zero_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('confidence 0..100');

        new ManageDecision('HOLD', -1);
    }

    #[Test]
    public function test_constructor_confidence_above_hundred_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('confidence 0..100');

        new ManageDecision('HOLD', 101);
    }

    #[Test]
    public function test_constructor_qty_delta_factor_below_minus_one_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('qtyDeltaFactor -1..1');

        new ManageDecision('HOLD', 50, null, null, -1.1);
    }

    #[Test]
    public function test_constructor_qty_delta_factor_above_one_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('qtyDeltaFactor -1..1');

        new ManageDecision('HOLD', 50, null, null, 1.1);
    }

    #[Test]
    public function test_constructor_qty_delta_factor_null_allowed(): void
    {
        $decision = new ManageDecision('HOLD', 50, null, null, null);

        $this->assertNull($decision->qtyDeltaFactor);
    }

    #[Test]
    public function test_constructor_qty_delta_factor_boundary_values(): void
    {
        $decision1 = new ManageDecision('HOLD', 50, null, null, -1.0);
        $decision2 = new ManageDecision('HOLD', 50, null, null, 1.0);

        $this->assertSame(-1.0, $decision1->qtyDeltaFactor);
        $this->assertSame(1.0, $decision2->qtyDeltaFactor);
    }

    #[Test]
    public function test_to_array_method(): void
    {
        $decision = new ManageDecision('CLOSE', 85, 48000.0, 51000.0, -0.25, 'Risk management');

        $expected = [
            'action' => 'CLOSE',
            'confidence' => 85,
            'new_stop_loss' => 48000.0,
            'new_take_profit' => 51000.0,
            'qty_delta_factor' => -0.25,
            'reason' => 'Risk management',
        ];

        $this->assertSame($expected, $decision->toArray());
    }

    #[Test]
    public function test_to_array_with_null_values(): void
    {
        $decision = new ManageDecision('HOLD', 60);

        $expected = [
            'action' => 'HOLD',
            'confidence' => 60,
            'new_stop_loss' => null,
            'new_take_profit' => null,
            'qty_delta_factor' => null,
            'reason' => '',
        ];

        $this->assertSame($expected, $decision->toArray());
    }

    #[Test]
    public function test_edge_case_confidence_zero(): void
    {
        $decision = new ManageDecision('HOLD', 0);

        $this->assertSame(0, $decision->confidence);
    }

    #[Test]
    public function test_edge_case_confidence_hundred(): void
    {
        $decision = new ManageDecision('CLOSE', 100);

        $this->assertSame(100, $decision->confidence);
    }

    #[Test]
    public function test_edge_case_qty_delta_factor_zero(): void
    {
        $decision = new ManageDecision('HOLD', 50, null, null, 0.0);

        $this->assertSame(0.0, $decision->qtyDeltaFactor);
    }

    #[Test]
    public function test_edge_case_qty_delta_factor_small_decimals(): void
    {
        $decision = new ManageDecision('HOLD', 50, null, null, 0.001);

        $this->assertSame(0.001, $decision->qtyDeltaFactor);
    }
}
