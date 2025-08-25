<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\UsageCounter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UsageCounterTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function test_usage_counter_has_correct_table_name(): void
    {
        $usageCounter = new UsageCounter;

        $this->assertSame('usage_counters', $usageCounter->getTable());
    }

    #[Test]
    public function test_usage_counter_fillable_attributes(): void
    {
        $usageCounter = new UsageCounter;

        $expectedFillable = [
            'user_id',
            'service',
            'count',
            'period',
            'reset_at',
        ];

        $this->assertSame($expectedFillable, $usageCounter->getFillable());
    }

    #[Test]
    public function test_usage_counter_casts(): void
    {
        $usageCounter = new UsageCounter;

        $this->assertSame('integer', $usageCounter->getCasts()['count']);
        $this->assertSame('datetime', $usageCounter->getCasts()['reset_at']);
    }

    #[Test]
    public function test_scope_for_service(): void
    {
        $usageCounter = new UsageCounter;
        $query = $usageCounter->newQuery();

        $result = $usageCounter->scopeForService($query, 'ai_consensus');

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Builder::class, $result);
    }

    #[Test]
    public function test_scope_for_period(): void
    {
        $usageCounter = new UsageCounter;
        $query = $usageCounter->newQuery();

        $result = $usageCounter->scopeForPeriod($query, 'daily');

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Builder::class, $result);
    }

    #[Test]
    public function test_usage_counter_scope_methods_return_builder(): void
    {
        $usageCounter = new UsageCounter;

        // Test scope methods return Builder instances
        $this->assertInstanceOf(
            \Illuminate\Database\Eloquent\Builder::class,
            $usageCounter->scopeForService($usageCounter->newQuery(), 'ai_consensus')
        );

        $this->assertInstanceOf(
            \Illuminate\Database\Eloquent\Builder::class,
            $usageCounter->scopeForPeriod($usageCounter->newQuery(), 'daily')
        );
    }

    #[Test]
    public function test_usage_counter_has_correct_attributes(): void
    {
        $usageCounter = new UsageCounter;

        // Test that the model has the expected attributes
        $this->assertTrue(in_array('user_id', $usageCounter->getFillable()));
        $this->assertTrue(in_array('service', $usageCounter->getFillable()));
        $this->assertTrue(in_array('count', $usageCounter->getFillable()));
        $this->assertTrue(in_array('period', $usageCounter->getFillable()));
        $this->assertTrue(in_array('reset_at', $usageCounter->getFillable()));
    }

    #[Test]
    public function test_usage_counter_casts_are_correct(): void
    {
        $usageCounter = new UsageCounter;
        $casts = $usageCounter->getCasts();

        $this->assertSame('integer', $casts['count']);
        $this->assertSame('datetime', $casts['reset_at']);
    }
}
