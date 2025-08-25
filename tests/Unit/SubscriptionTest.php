<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Subscription;
use PHPUnit\Framework\TestCase;

class SubscriptionTest extends TestCase
{
    #[Test]
    public function test_subscription_has_correct_table_name(): void
    {
        $subscription = new Subscription;

        $this->assertSame('subscriptions', $subscription->getTable());
    }

    #[Test]
    public function test_subscription_fillable_attributes(): void
    {
        $subscription = new Subscription;

        $expectedFillable = [
            'user_id',
            'plan_id',
            'status',
            'starts_at',
            'expires_at',
            'meta',
        ];

        $this->assertSame($expectedFillable, $subscription->getFillable());
    }

    #[Test]
    public function test_subscription_casts(): void
    {
        $subscription = new Subscription;

        $this->assertSame('datetime', $subscription->getCasts()['starts_at']);
        $this->assertSame('datetime', $subscription->getCasts()['expires_at']);
        $this->assertSame('array', $subscription->getCasts()['meta']);
    }

    #[Test]
    public function test_subscription_saas_billing_fields(): void
    {
        $subscription = new Subscription;

        // SaaS billing essential fields
        $this->assertTrue(in_array('user_id', $subscription->getFillable()));
        $this->assertTrue(in_array('plan_id', $subscription->getFillable()));
        $this->assertTrue(in_array('status', $subscription->getFillable()));
        $this->assertTrue(in_array('starts_at', $subscription->getFillable()));
        $this->assertTrue(in_array('expires_at', $subscription->getFillable()));
    }

    #[Test]
    public function test_subscription_has_scope_methods(): void
    {
        $subscription = new Subscription;

        // Verify scope methods exist
        $this->assertTrue(method_exists($subscription, 'scopeActive'));
        $this->assertTrue(method_exists($subscription, 'scopeExpired'));
    }

    #[Test]
    public function test_subscription_has_relationship_methods(): void
    {
        $subscription = new Subscription;

        // Verify relationship methods exist
        $this->assertTrue(method_exists($subscription, 'user'));
        $this->assertTrue(method_exists($subscription, 'plan'));
    }

    #[Test]
    public function test_subscription_meta_field_for_extensibility(): void
    {
        $subscription = new Subscription;

        // Meta field for future SaaS features
        $this->assertTrue(in_array('meta', $subscription->getFillable()));
        $this->assertSame('array', $subscription->getCasts()['meta']);
    }

    #[Test]
    public function test_subscription_datetime_fields_cast(): void
    {
        $subscription = new Subscription;

        // SaaS subscription lifecycle dates
        $this->assertSame('datetime', $subscription->getCasts()['starts_at']);
        $this->assertSame('datetime', $subscription->getCasts()['expires_at']);
    }

    #[Test]
    public function test_subscription_model_structure(): void
    {
        $subscription = new Subscription;

        // Verify model is final (immutable structure)
        $reflection = new \ReflectionClass($subscription);
        $this->assertTrue($reflection->isFinal());

        // Verify HasFactory trait
        $this->assertTrue(in_array('Illuminate\Database\Eloquent\Factories\HasFactory', class_uses($subscription)));
    }
}
