<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\AiLog;
use PHPUnit\Framework\TestCase;

class AiLogTest extends TestCase
{
    #[Test]
    public function test_ai_log_fillable_attributes(): void
    {
        $aiLog = new AiLog;

        $expectedFillable = [
            'cycle_uuid', 'symbol', 'provider', 'stage', 'action', 'confidence',
            'input_ctx', 'raw_output', 'latency_ms', 'reason',
        ];

        $this->assertSame($expectedFillable, $aiLog->getFillable());
    }

    #[Test]
    public function test_ai_log_casts(): void
    {
        $aiLog = new AiLog;
        $casts = $aiLog->getCasts();

        $this->assertSame('array', $casts['input_ctx']);
        $this->assertSame('array', $casts['raw_output']);
    }

    #[Test]
    public function test_ai_log_trading_attributes(): void
    {
        $aiLog = new AiLog;

        // Core trading attributes
        $this->assertTrue(in_array('symbol', $aiLog->getFillable()));
        $this->assertTrue(in_array('action', $aiLog->getFillable()));
        $this->assertTrue(in_array('confidence', $aiLog->getFillable()));
    }

    #[Test]
    public function test_ai_log_cycle_tracking(): void
    {
        $aiLog = new AiLog;

        // Cycle tracking
        $this->assertTrue(in_array('cycle_uuid', $aiLog->getFillable()));
    }

    #[Test]
    public function test_ai_log_provider_tracking(): void
    {
        $aiLog = new AiLog;

        // AI provider tracking
        $this->assertTrue(in_array('provider', $aiLog->getFillable()));
    }

    #[Test]
    public function test_ai_log_stage_tracking(): void
    {
        $aiLog = new AiLog;

        // Stage tracking
        $this->assertTrue(in_array('stage', $aiLog->getFillable()));
    }

    #[Test]
    public function test_ai_log_input_context(): void
    {
        $aiLog = new AiLog;

        // Input context storage
        $this->assertTrue(in_array('input_ctx', $aiLog->getFillable()));
    }

    #[Test]
    public function test_ai_log_raw_output(): void
    {
        $aiLog = new AiLog;

        // Raw output storage
        $this->assertTrue(in_array('raw_output', $aiLog->getFillable()));
    }

    #[Test]
    public function test_ai_log_performance_tracking(): void
    {
        $aiLog = new AiLog;

        // Performance tracking
        $this->assertTrue(in_array('latency_ms', $aiLog->getFillable()));
    }

    #[Test]
    public function test_ai_log_reason_tracking(): void
    {
        $aiLog = new AiLog;

        // Reason tracking
        $this->assertTrue(in_array('reason', $aiLog->getFillable()));
    }

    #[Test]
    public function test_ai_log_input_context_array_cast(): void
    {
        $aiLog = new AiLog;

        // Input context should be array for structured data
        $this->assertSame('array', $aiLog->getCasts()['input_ctx']);
    }

    #[Test]
    public function test_ai_log_raw_output_array_cast(): void
    {
        $aiLog = new AiLog;

        // Raw output should be array for AI provider responses
        $this->assertSame('array', $aiLog->getCasts()['raw_output']);
    }

    #[Test]
    public function test_ai_log_ai_provider_audit_ready(): void
    {
        $aiLog = new AiLog;

        // AI provider audit essential fields
        $fillable = $aiLog->getFillable();

        // Provider identification
        $this->assertTrue(in_array('provider', $fillable));
        $this->assertTrue(in_array('stage', $fillable));

        // Decision tracking
        $this->assertTrue(in_array('action', $fillable));
        $this->assertTrue(in_array('confidence', $fillable));
    }

    #[Test]
    public function test_ai_log_trading_cycle_ready(): void
    {
        $aiLog = new AiLog;

        // Trading cycle essential fields
        $fillable = $aiLog->getFillable();

        // Cycle identification
        $this->assertTrue(in_array('cycle_uuid', $fillable));
        $this->assertTrue(in_array('symbol', $fillable));
    }

    #[Test]
    public function test_ai_log_performance_monitoring_ready(): void
    {
        $aiLog = new AiLog;

        // Performance monitoring essential fields
        $fillable = $aiLog->getFillable();

        // Latency tracking
        $this->assertTrue(in_array('latency_ms', $fillable));
    }

    #[Test]
    public function test_ai_log_debugging_ready(): void
    {
        $aiLog = new AiLog;

        // Debugging essential fields
        $fillable = $aiLog->getFillable();

        // Context and output
        $this->assertTrue(in_array('input_ctx', $fillable));
        $this->assertTrue(in_array('raw_output', $fillable));
        $this->assertTrue(in_array('reason', $fillable));
    }

    #[Test]
    public function test_ai_log_analytics_ready(): void
    {
        $aiLog = new AiLog;

        // Analytics essential fields
        $fillable = $aiLog->getFillable();

        // Performance metrics
        $this->assertTrue(in_array('latency_ms', $fillable));
        $this->assertTrue(in_array('confidence', $fillable));

        // Decision analysis
        $this->assertTrue(in_array('action', $fillable));
        $this->assertTrue(in_array('stage', $fillable));
    }

    #[Test]
    public function test_ai_log_model_structure(): void
    {
        $aiLog = new AiLog;

        // Verify model structure
        $reflection = new \ReflectionClass($aiLog);

        $this->assertFalse($reflection->isFinal()); // Model should be extensible
        $this->assertTrue($reflection->isSubclassOf(\Illuminate\Database\Eloquent\Model::class));
    }

    #[Test]
    public function test_ai_log_data_integrity(): void
    {
        $aiLog = new AiLog;

        // Data integrity checks
        $fillable = $aiLog->getFillable();
        $casts = $aiLog->getCasts();

        // All fillable fields should have corresponding casts where needed
        $this->assertTrue(in_array('input_ctx', array_keys($casts)));
        $this->assertTrue(in_array('raw_output', array_keys($casts)));
    }

    #[Test]
    public function test_ai_log_saas_observability_ready(): void
    {
        $aiLog = new AiLog;

        // SaaS observability essential fields
        $fillable = $aiLog->getFillable();

        // Performance monitoring
        $this->assertTrue(in_array('latency_ms', $fillable));

        // Decision tracking
        $this->assertTrue(in_array('action', $fillable));
        $this->assertTrue(in_array('confidence', $fillable));

        // Context preservation
        $this->assertTrue(in_array('input_ctx', $fillable));
        $this->assertTrue(in_array('raw_output', $fillable));
    }
}
