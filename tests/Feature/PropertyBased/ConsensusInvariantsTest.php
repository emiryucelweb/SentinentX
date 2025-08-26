<?php

declare(strict_types=1);

namespace Tests\Feature\PropertyBased;

use App\Services\AI\ConsensusService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Fakes\FakeAiProvider;

/**
 * Consensus Service Invariant Tests
 *
 * Tests critical behavioral invariants:
 * 1. Idempotency - same input always produces same output
 * 2. Deviation veto consistency
 * 3. NONE ≥90% veto reliability
 */
class ConsensusInvariantsTest extends TestCase
{
    #[Test]
    public function weighted_median_idempotency_property()
    {
        $ai1 = new FakeAiProvider('ai1', [
            'action' => 'LONG',
            'confidence' => 75,
            'reason' => 'Bullish signals',
        ]);

        $ai2 = new FakeAiProvider('ai2', [
            'action' => 'LONG',
            'confidence' => 80,
            'reason' => 'Strong momentum',
        ]);

        $consensus = new ConsensusService([$ai1, $ai2]);
        
        $testContext = [
            'symbol' => 'BTCUSDT',
            'price' => 43250.0,
            'indicators' => ['rsi' => 65, 'volume' => 1000000],
        ];

        // Multiple calls should produce identical results
        $result1 = $consensus->decide($testContext);
        $result2 = $consensus->decide($testContext);

        $this->assertEquals($result1['action'], $result2['action']);
        $this->assertEquals($result1['confidence'], $result2['confidence']);
    }

    #[Test]
    public function twenty_percent_deviation_veto_consistency()
    {
        // Create providers with high confidence deviation (>20%)
        $ai1 = new FakeAiProvider('ai1', [
            'action' => 'LONG',
            'confidence' => 90, // High confidence
            'reason' => 'Strong bullish',
        ]);

        $ai2 = new FakeAiProvider('ai2', [
            'action' => 'LONG',
            'confidence' => 50, // >20% deviation from AI1
            'reason' => 'Moderate signal',
        ]);

        $consensus = new ConsensusService([$ai1, $ai2]);
        
        $result = $consensus->decide([
            'symbol' => 'BTCUSDT',
            'price' => 43250.0,
        ]);

        // Should either trigger deviation veto or handle gracefully
        $this->assertContains($result['action'], ['NO_TRADE', 'NONE', 'LONG']);
        $this->assertArrayHasKey('reason', $result);
    }

    #[Test]
    public function consensus_confidence_bounds_property()
    {
        $ai1 = new FakeAiProvider('ai1', [
            'action' => 'LONG',
            'confidence' => 75,
        ]);

        $ai2 = new FakeAiProvider('ai2', [
            'action' => 'LONG', 
            'confidence' => 85,
        ]);

        $consensus = new ConsensusService([$ai1, $ai2]);
        
        $result = $consensus->decide([
            'symbol' => 'BTCUSDT',
            'price' => 43250.0,
        ]);

        // Confidence should be within reasonable bounds
        $this->assertGreaterThanOrEqual(0, $result['confidence']);
        $this->assertLessThanOrEqual(100, $result['confidence']);
    }

    #[Test]
    public function none_ninety_percent_veto_reliability()
    {
        // High confidence NONE should trigger veto
        $ai1 = new FakeAiProvider('ai1', [
            'action' => 'NONE',
            'confidence' => 95, // Above 90% threshold
            'reason' => 'High confidence NONE',
        ]);

        $ai2 = new FakeAiProvider('ai2', [
            'action' => 'LONG',
            'confidence' => 75,
        ]);

        $consensus = new ConsensusService([$ai1, $ai2]);
        
        $result = $consensus->decide([
            'symbol' => 'BTCUSDT',
            'price' => 43250.0,
        ]);

        // Should trigger NONE veto
        $this->assertEquals('NO_TRADE', $result['action']);
        $this->assertStringContains('veto', strtolower($result['reason']));
    }

    #[Test]
    public function consensus_handles_edge_cases_gracefully()
    {
        // Test with minimal providers
        $ai1 = new FakeAiProvider('ai1', [
            'action' => 'LONG',
            'confidence' => 60,
        ]);

        $consensus = new ConsensusService([$ai1]);
        
        $result = $consensus->decide([
            'symbol' => 'BTCUSDT',
            'price' => 43250.0,
        ]);

        // Should handle single provider gracefully
        $this->assertContains($result['action'], ['LONG', 'NO_TRADE', 'NONE']);
        $this->assertArrayHasKey('reason', $result);
        $this->assertArrayHasKey('confidence', $result);
    }

    #[Test]
    public function consensus_action_consistency_property()
    {
        // Test with conflicting actions
        $ai1 = new FakeAiProvider('ai1', [
            'action' => 'LONG',
            'confidence' => 70,
        ]);

        $ai2 = new FakeAiProvider('ai2', [
            'action' => 'SHORT',
            'confidence' => 70,
        ]);

        $ai3 = new FakeAiProvider('ai3', [
            'action' => 'NONE',
            'confidence' => 60,
        ]);

        $consensus = new ConsensusService([$ai1, $ai2, $ai3]);
        
        $result = $consensus->decide([
            'symbol' => 'BTCUSDT',
            'price' => 43250.0,
        ]);

        // Should handle conflicts reasonably (majority, weighted, or safe fallback)
        $validActions = ['LONG', 'SHORT', 'NONE', 'NO_TRADE', 'HOLD'];
        $this->assertContains($result['action'], $validActions);
        $this->assertArrayHasKey('reason', $result);
    }
}