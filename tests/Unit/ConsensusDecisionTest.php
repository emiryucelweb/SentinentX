<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\ConsensusDecision;
use PHPUnit\Framework\TestCase;

class ConsensusDecisionTest extends TestCase
{
    #[Test]
    public function test_consensus_decision_fillable_attributes(): void
    {
        $decision = new ConsensusDecision;

        $expectedFillable = [
            'cycle_uuid', 'symbol', 'round1', 'round2', 'final_action', 'final_confidence', 'majority_lock',
        ];

        $this->assertSame($expectedFillable, $decision->getFillable());
    }

    #[Test]
    public function test_consensus_decision_casts(): void
    {
        $decision = new ConsensusDecision;
        $casts = $decision->getCasts();

        $this->assertSame('array', $casts['round1']);
        $this->assertSame('array', $casts['round2']);
        $this->assertSame('boolean', $casts['majority_lock']);
    }

    #[Test]
    public function test_consensus_decision_trading_attributes(): void
    {
        $decision = new ConsensusDecision;

        // Core trading attributes
        $this->assertTrue(in_array('symbol', $decision->getFillable()));
        $this->assertTrue(in_array('final_action', $decision->getFillable()));
        $this->assertTrue(in_array('final_confidence', $decision->getFillable()));
    }

    #[Test]
    public function test_consensus_decision_cycle_tracking(): void
    {
        $decision = new ConsensusDecision;

        // Cycle tracking
        $this->assertTrue(in_array('cycle_uuid', $decision->getFillable()));
    }

    #[Test]
    public function test_consensus_decision_round_data(): void
    {
        $decision = new ConsensusDecision;

        // Round data storage
        $this->assertTrue(in_array('round1', $decision->getFillable()));
        $this->assertTrue(in_array('round2', $decision->getFillable()));
    }

    #[Test]
    public function test_consensus_decision_majority_lock(): void
    {
        $decision = new ConsensusDecision;

        // Majority lock mechanism
        $this->assertTrue(in_array('majority_lock', $decision->getFillable()));
    }

    #[Test]
    public function test_consensus_decision_round1_array_cast(): void
    {
        $decision = new ConsensusDecision;

        // Round1 should be array for AI provider decisions
        $this->assertSame('array', $decision->getCasts()['round1']);
    }

    #[Test]
    public function test_consensus_decision_round2_array_cast(): void
    {
        $decision = new ConsensusDecision;

        // Round2 should be array for consensus calculations
        $this->assertSame('array', $decision->getCasts()['round2']);
    }

    #[Test]
    public function test_consensus_decision_majority_lock_boolean_cast(): void
    {
        $decision = new ConsensusDecision;

        // Majority lock should be boolean
        $this->assertSame('boolean', $decision->getCasts()['majority_lock']);
    }

    #[Test]
    public function test_consensus_decision_ai_consensus_ready(): void
    {
        $decision = new ConsensusDecision;

        // AI consensus essential fields
        $fillable = $decision->getFillable();

        // Decision data
        $this->assertTrue(in_array('final_action', $fillable));
        $this->assertTrue(in_array('final_confidence', $fillable));

        // Round data
        $this->assertTrue(in_array('round1', $fillable));
        $this->assertTrue(in_array('round2', $fillable));
    }

    #[Test]
    public function test_consensus_decision_trading_cycle_ready(): void
    {
        $decision = new ConsensusDecision;

        // Trading cycle essential fields
        $fillable = $decision->getFillable();

        // Cycle identification
        $this->assertTrue(in_array('cycle_uuid', $fillable));
        $this->assertTrue(in_array('symbol', $fillable));
    }

    #[Test]
    public function test_consensus_decision_majority_mechanism_ready(): void
    {
        $decision = new ConsensusDecision;

        // Majority mechanism essential fields
        $fillable = $decision->getFillable();

        // Majority lock
        $this->assertTrue(in_array('majority_lock', $fillable));
    }

    #[Test]
    public function test_consensus_decision_analytics_ready(): void
    {
        $decision = new ConsensusDecision;

        // Analytics essential fields
        $fillable = $decision->getFillable();

        // Decision tracking
        $this->assertTrue(in_array('final_action', $fillable));
        $this->assertTrue(in_array('final_confidence', $fillable));

        // Round analysis
        $this->assertTrue(in_array('round1', $fillable));
        $this->assertTrue(in_array('round2', $fillable));
    }

    #[Test]
    public function test_consensus_decision_model_structure(): void
    {
        $decision = new ConsensusDecision;

        // Verify model structure
        $reflection = new \ReflectionClass($decision);

        $this->assertFalse($reflection->isFinal()); // Model should be extensible
        $this->assertTrue($reflection->isSubclassOf(\Illuminate\Database\Eloquent\Model::class));
    }

    #[Test]
    public function test_consensus_decision_data_integrity(): void
    {
        $decision = new ConsensusDecision;

        // Data integrity checks
        $fillable = $decision->getFillable();
        $casts = $decision->getCasts();

        // All fillable fields should have corresponding casts
        $this->assertTrue(in_array('round1', array_keys($casts)));
        $this->assertTrue(in_array('round2', array_keys($casts)));
        $this->assertTrue(in_array('majority_lock', array_keys($casts)));
    }
}
