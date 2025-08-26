<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Subscription>
 */
class SubscriptionFactory extends Factory
{
    protected $model = Subscription::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'plan' => $this->faker->randomElement(['starter', 'pro', 'enterprise']),
            'status' => 'active',
            'started_at' => now(),
            'ends_at' => now()->addMonth(),
            'billing_cycle' => 'monthly',
            'monthly_price' => 29.00,
            'currency' => 'USD',
            'api_quota' => 1000,
            'trade_quota' => 50,
            'auto_renew' => true,
            'last_billing_date' => now()->subDay(),
            'next_billing_date' => now()->addMonth(),
        ];
    }

    /**
     * Indicate that the subscription is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
            'started_at' => now(),
            'ends_at' => now()->addMonth(),
        ]);
    }

    /**
     * Indicate that the subscription is expired.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'expired',
            'started_at' => now()->subMonth(),
            'ends_at' => now()->subDay(),
        ]);
    }

    /**
     * Indicate that the subscription is cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
            'auto_renew' => false,
        ]);
    }

    /**
     * Set a specific plan type.
     */
    public function plan(string $plan): static
    {
        $quotas = [
            'free' => ['api_quota' => 100, 'trade_quota' => 5, 'monthly_price' => 0.00],
            'starter' => ['api_quota' => 1000, 'trade_quota' => 50, 'monthly_price' => 29.00],
            'pro' => ['api_quota' => 5000, 'trade_quota' => 250, 'monthly_price' => 99.00],
            'enterprise' => ['api_quota' => 50000, 'trade_quota' => 1000, 'monthly_price' => 299.00],
        ];

        return $this->state(fn (array $attributes) => [
            'plan' => $plan,
            ...$quotas[$plan] ?? $quotas['starter'],
        ]);
    }
}
