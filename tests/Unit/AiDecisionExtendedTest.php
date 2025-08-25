<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\DTO\AiDecision;
use Tests\TestCase;

class AiDecisionExtendedTest extends TestCase
{
    public function test_ai_decision_from_array_complete()
    {
        $data = [
            'action' => 'LONG',
            'confidence' => 85,
            'stop_loss' => 29000.0,
            'take_profit' => 32000.0,
            'qty_delta_factor' => 0.8, // Within -1..1 range
            'reason' => 'Strong bullish signal',
            'raw' => ['signal' => 'BUY', 'strength' => 0.85],
        ];

        $decision = AiDecision::fromArray($data);

        $this->assertEquals('LONG', $decision->action);
        $this->assertEquals(85, $decision->confidence);
        $this->assertEquals(29000.0, $decision->stopLoss);
        $this->assertEquals(32000.0, $decision->takeProfit);
        $this->assertEquals(0.8, $decision->qtyDeltaFactor);
        $this->assertEquals('Strong bullish signal', $decision->reason);
        $this->assertEquals(['signal' => 'BUY', 'strength' => 0.85], $decision->raw);
    }

    public function test_ai_decision_from_array_minimal()
    {
        $data = [
            'action' => 'SHORT',
            'confidence' => 70,
        ];

        $decision = AiDecision::fromArray($data);

        $this->assertEquals('SHORT', $decision->action);
        $this->assertEquals(70, $decision->confidence);
        $this->assertNull($decision->stopLoss);
        $this->assertNull($decision->takeProfit);
        $this->assertNull($decision->qtyDeltaFactor);
        $this->assertEquals('', $decision->reason); // Default is empty string, not null
        $this->assertNull($decision->raw);
    }

    public function test_ai_decision_from_array_hold_action()
    {
        $data = [
            'action' => 'HOLD',
            'confidence' => 95,
            'reason' => 'Market uncertainty',
        ];

        $decision = AiDecision::fromArray($data);

        $this->assertEquals('HOLD', $decision->action);
        $this->assertEquals(95, $decision->confidence);
        $this->assertEquals('Market uncertainty', $decision->reason);
    }

    public function test_ai_decision_from_array_invalid_action()
    {
        // Invalid actions are normalized to HOLD, so no exception expected
        $data = [
            'action' => 'INVALID_ACTION',
            'confidence' => 80,
        ];

        $decision = AiDecision::fromArray($data);
        $this->assertEquals('HOLD', $decision->action); // Fallback to HOLD
    }

    public function test_ai_decision_from_array_missing_action()
    {
        // Missing action defaults to HOLD
        $data = [
            'confidence' => 80,
            // Missing action
        ];

        $decision = AiDecision::fromArray($data);
        $this->assertEquals('HOLD', $decision->action); // Default to HOLD
    }

    public function test_ai_decision_from_array_missing_confidence()
    {
        // Missing confidence defaults to 0
        $data = [
            'action' => 'LONG',
            // Missing confidence
        ];

        $decision = AiDecision::fromArray($data);
        $this->assertEquals(0, $decision->confidence); // Default to 0
    }

    public function test_ai_decision_confidence_boundaries()
    {
        // Test minimum confidence
        $data = ['action' => 'LONG', 'confidence' => 0];
        $decision = AiDecision::fromArray($data);
        $this->assertEquals(0, $decision->confidence);

        // Test maximum confidence
        $data = ['action' => 'SHORT', 'confidence' => 100];
        $decision = AiDecision::fromArray($data);
        $this->assertEquals(100, $decision->confidence);
    }

    public function test_ai_decision_type_casting()
    {
        $data = [
            'action' => 'LONG',
            'confidence' => '85', // String should be cast to int
            'stop_loss' => '29000', // String should be cast to float
            'take_profit' => '32000.5',
            'qty_delta_factor' => '0.5',
        ];

        $decision = AiDecision::fromArray($data);

        $this->assertIsInt($decision->confidence);
        $this->assertIsFloat($decision->stopLoss);
        $this->assertIsFloat($decision->takeProfit);
        $this->assertIsFloat($decision->qtyDeltaFactor);

        $this->assertEquals(85, $decision->confidence);
        $this->assertEquals(29000.0, $decision->stopLoss);
        $this->assertEquals(32000.5, $decision->takeProfit);
        $this->assertEquals(0.5, $decision->qtyDeltaFactor);
    }

    public function test_ai_decision_with_null_values()
    {
        $data = [
            'action' => 'HOLD',
            'confidence' => 60,
            'stop_loss' => null,
            'take_profit' => null,
            'qty_delta_factor' => null,
            'reason' => null,
            'raw' => null,
        ];

        $decision = AiDecision::fromArray($data);

        $this->assertEquals('HOLD', $decision->action);
        $this->assertEquals(60, $decision->confidence);
        $this->assertNull($decision->stopLoss);
        $this->assertNull($decision->takeProfit);
        $this->assertNull($decision->qtyDeltaFactor);
        $this->assertEquals('', $decision->reason); // Empty string, not null
        $this->assertNull($decision->raw);
    }

    public function test_ai_decision_serialization()
    {
        $data = [
            'action' => 'LONG',
            'confidence' => 85,
            'stop_loss' => 29000.0,
            'take_profit' => 32000.0,
            'reason' => 'Strong signal',
        ];

        $decision = AiDecision::fromArray($data);

        // Test that the object can be serialized and unserialized
        $serialized = serialize($decision);
        $unserialized = unserialize($serialized);

        $this->assertEquals($decision->action, $unserialized->action);
        $this->assertEquals($decision->confidence, $unserialized->confidence);
        $this->assertEquals($decision->stopLoss, $unserialized->stopLoss);
        $this->assertEquals($decision->takeProfit, $unserialized->takeProfit);
        $this->assertEquals($decision->reason, $unserialized->reason);
    }
}
