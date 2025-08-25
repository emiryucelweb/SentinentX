<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\DTO\AiDecision;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class AiDecisionTest extends TestCase
{
    #[Test]
    public function test_ai_decision_constructor_with_valid_parameters(): void
    {
        $decision = new AiDecision('LONG', 85, 48000.0, 52000.0, 0.5, 'Strong bullish signal', ['leverage' => 10]);

        $this->assertSame('LONG', $decision->action);
        $this->assertSame(85, $decision->confidence);
        $this->assertSame(48000.0, $decision->stopLoss);
        $this->assertSame(52000.0, $decision->takeProfit);
        $this->assertSame(0.5, $decision->qtyDeltaFactor);
        $this->assertSame('Strong bullish signal', $decision->reason);
        $this->assertSame(['leverage' => 10], $decision->raw);
    }

    #[Test]
    public function test_ai_decision_action_uppercase_conversion(): void
    {
        $decision = new AiDecision('long', 75);

        $this->assertSame('LONG', $decision->action);
    }

    #[Test]
    public function test_ai_decision_with_minimal_parameters(): void
    {
        $decision = new AiDecision('HOLD', 60);

        $this->assertSame('HOLD', $decision->action);
        $this->assertSame(60, $decision->confidence);
        $this->assertNull($decision->stopLoss);
        $this->assertNull($decision->takeProfit);
        $this->assertNull($decision->qtyDeltaFactor);
        $this->assertSame('', $decision->reason);
        $this->assertNull($decision->raw);
    }

    #[Test]
    public function test_ai_decision_invalid_action_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Geçersiz action: INVALID');

        new AiDecision('INVALID', 50);
    }

    #[Test]
    public function test_ai_decision_confidence_below_zero_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('confidence 0..100');

        new AiDecision('HOLD', -1);
    }

    #[Test]
    public function test_ai_decision_confidence_above_hundred_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('confidence 0..100');

        new AiDecision('HOLD', 101);
    }

    #[Test]
    public function test_ai_decision_qty_delta_factor_below_minus_one_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('qtyDeltaFactor -1..1');

        new AiDecision('HOLD', 50, null, null, -1.1);
    }

    #[Test]
    public function test_ai_decision_qty_delta_factor_above_one_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('qtyDeltaFactor -1..1');

        new AiDecision('HOLD', 50, null, null, 1.1);
    }

    #[Test]
    public function test_ai_decision_from_array_method(): void
    {
        $array = [
            'action' => 'SHORT',
            'confidence' => 90,
            'stop_loss' => 51000.0,
            'take_profit' => 49000.0,
            'qty_delta_factor' => -0.25,
            'reason' => 'Bearish momentum',
            'raw' => ['leverage' => 5],
        ];

        $decision = AiDecision::fromArray($array);

        $this->assertSame('SHORT', $decision->action);
        $this->assertSame(90, $decision->confidence);
        $this->assertSame(51000.0, $decision->stopLoss);
        $this->assertSame(49000.0, $decision->takeProfit);
        $this->assertSame(-0.25, $decision->qtyDeltaFactor);
        $this->assertSame('Bearish momentum', $decision->reason);
        $this->assertSame(['leverage' => 5], $decision->raw);
    }

    #[Test]
    public function test_ai_decision_from_array_with_defaults(): void
    {
        $array = ['action' => 'HOLD'];

        $decision = AiDecision::fromArray($array);

        $this->assertSame('HOLD', $decision->action);
        $this->assertSame(0, $decision->confidence);
        $this->assertNull($decision->stopLoss);
        $this->assertNull($decision->takeProfit);
        $this->assertNull($decision->qtyDeltaFactor);
        $this->assertSame('', $decision->reason);
        $this->assertNull($decision->raw);
    }

    #[Test]
    public function test_ai_decision_to_array_method(): void
    {
        $decision = new AiDecision('CLOSE', 80, 50000.0, 51000.0, 0.0, 'Take profit hit', ['meta' => 'data']);

        $expected = [
            'action' => 'CLOSE',
            'confidence' => 80,
            'stop_loss' => 50000.0,
            'take_profit' => 51000.0,
            'qty_delta_factor' => 0.0,
            'reason' => 'Take profit hit',
            'raw' => ['meta' => 'data'],
        ];

        $this->assertSame($expected, $decision->toArray());
    }

    #[Test]
    public function test_ai_decision_is_open_intent_methods(): void
    {
        $longDecision = new AiDecision('LONG', 70);
        $shortDecision = new AiDecision('SHORT', 70);
        $holdDecision = new AiDecision('HOLD', 70);

        $this->assertTrue($longDecision->isOpenIntent());
        $this->assertTrue($shortDecision->isOpenIntent());
        $this->assertFalse($holdDecision->isOpenIntent());
    }

    #[Test]
    public function test_ai_decision_is_no_trade_methods(): void
    {
        $noTradeDecision = new AiDecision('NO_TRADE', 70);
        $noOpenDecision = new AiDecision('NO_OPEN', 70);
        $holdDecision = new AiDecision('HOLD', 70);

        $this->assertTrue($noTradeDecision->isNoTrade());
        $this->assertTrue($noOpenDecision->isNoTrade());
        $this->assertFalse($holdDecision->isNoTrade());
    }

    #[Test]
    public function test_ai_decision_action_normalization(): void
    {
        // Test various action formats
        $testCases = [
            'BUY' => 'LONG',
            'SELL' => 'SHORT',
            'WAIT' => 'HOLD',
            'EXIT' => 'CLOSE',
            'NO_ACTION' => 'NO_TRADE',
            'LONG' => 'LONG',
            'SHORT' => 'SHORT',
            'HOLD' => 'HOLD',
        ];

        foreach ($testCases as $input => $expected) {
            $decision = AiDecision::fromArray(['action' => $input]);
            $this->assertSame($expected, $decision->action, "Input: {$input}");
        }
    }

    #[Test]
    public function test_ai_decision_constants(): void
    {
        $this->assertSame('LONG', AiDecision::ACTION_LONG);
        $this->assertSame('SHORT', AiDecision::ACTION_SHORT);
        $this->assertSame('HOLD', AiDecision::ACTION_HOLD);
        $this->assertSame('CLOSE', AiDecision::ACTION_CLOSE);
        $this->assertSame('NO_TRADE', AiDecision::ACTION_NO_TRADE);
        $this->assertSame('NO_OPEN', AiDecision::ACTION_NO_OPEN);
    }

    #[Test]
    public function test_ai_decision_edge_case_confidence_zero(): void
    {
        $decision = new AiDecision('HOLD', 0);

        $this->assertSame(0, $decision->confidence);
    }

    #[Test]
    public function test_ai_decision_edge_case_confidence_hundred(): void
    {
        $decision = new AiDecision('HOLD', 100);

        $this->assertSame(100, $decision->confidence);
    }

    #[Test]
    public function test_ai_decision_edge_case_qty_delta_factor_boundaries(): void
    {
        $decision1 = new AiDecision('HOLD', 50, null, null, -1.0);
        $decision2 = new AiDecision('HOLD', 50, null, null, 1.0);

        $this->assertSame(-1.0, $decision1->qtyDeltaFactor);
        $this->assertSame(1.0, $decision2->qtyDeltaFactor);
    }

    #[Test]
    public function test_ai_decision_saas_api_ready(): void
    {
        $decision = new AiDecision('LONG', 85, 48000.0, 52000.0, 0.5, 'AI signal', ['leverage' => 10]);

        // Test serialization for API
        $array = $decision->toArray();

        $this->assertArrayHasKey('action', $array);
        $this->assertArrayHasKey('confidence', $array);
        $this->assertArrayHasKey('stop_loss', $array);
        $this->assertArrayHasKey('take_profit', $array);
        $this->assertArrayHasKey('qty_delta_factor', $array);
        $this->assertArrayHasKey('reason', $array);
        $this->assertArrayHasKey('raw', $array);
    }

    #[Test]
    public function test_ai_decision_immutable_structure(): void
    {
        $reflection = new \ReflectionClass(AiDecision::class);

        $this->assertTrue($reflection->isFinal());

        // All properties should be public for API access
        $properties = $reflection->getProperties();
        foreach ($properties as $property) {
            $this->assertTrue($property->isPublic(), "Property {$property->getName()} should be public");
        }
    }
}
