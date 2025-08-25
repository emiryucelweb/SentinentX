<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\AiProvider;
use PHPUnit\Framework\TestCase;

class AiProviderTest extends TestCase
{
    #[Test]
    public function test_ai_provider_has_correct_fillable_attributes(): void
    {
        $aiProvider = new AiProvider;

        $expectedFillable = [
            'name', 'enabled', 'model', 'timeout_ms', 'max_tokens',
            'priority', 'weight', 'cost_per_1k_tokens', 'meta',
        ];

        $this->assertSame($expectedFillable, $aiProvider->getFillable());
    }

    #[Test]
    public function test_ai_provider_casts_are_correct(): void
    {
        $aiProvider = new AiProvider;
        $casts = $aiProvider->getCasts();

        $this->assertSame('boolean', $casts['enabled']);
        $this->assertSame('decimal:2', $casts['weight']);
        $this->assertSame('decimal:4', $casts['cost_per_1k_tokens']);
        $this->assertSame('array', $casts['meta']);
    }

    #[Test]
    public function test_ai_provider_model_attributes(): void
    {
        $aiProvider = new AiProvider;

        // Test model structure
        $this->assertTrue(in_array('name', $aiProvider->getFillable()));
        $this->assertTrue(in_array('enabled', $aiProvider->getFillable()));
        $this->assertTrue(in_array('model', $aiProvider->getFillable()));
        $this->assertTrue(in_array('weight', $aiProvider->getFillable()));
        $this->assertTrue(in_array('cost_per_1k_tokens', $aiProvider->getFillable()));
    }

    #[Test]
    public function test_ai_provider_saas_cost_tracking(): void
    {
        $aiProvider = new AiProvider;

        // SaaS cost tracking attributes
        $this->assertTrue(in_array('cost_per_1k_tokens', $aiProvider->getFillable()));
        $this->assertTrue(in_array('priority', $aiProvider->getFillable()));
        $this->assertTrue(in_array('timeout_ms', $aiProvider->getFillable()));
        $this->assertTrue(in_array('max_tokens', $aiProvider->getFillable()));
    }

    #[Test]
    public function test_ai_provider_meta_configuration(): void
    {
        $aiProvider = new AiProvider;

        // Meta configuration for future extensibility
        $this->assertTrue(in_array('meta', $aiProvider->getFillable()));
        $this->assertSame('array', $aiProvider->getCasts()['meta']);
    }

    #[Test]
    public function test_ai_provider_weight_precision(): void
    {
        $aiProvider = new AiProvider;

        // Weight precision for AI consensus calculations
        $this->assertSame('decimal:2', $aiProvider->getCasts()['weight']);
    }

    #[Test]
    public function test_ai_provider_cost_precision(): void
    {
        $aiProvider = new AiProvider;

        // Cost precision for SaaS billing
        $this->assertSame('decimal:4', $aiProvider->getCasts()['cost_per_1k_tokens']);
    }
}
