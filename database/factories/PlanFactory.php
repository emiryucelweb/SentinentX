<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Plan>
 */
class PlanFactory extends Factory
{
    protected $model = Plan::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->randomElement(['starter', 'professional', 'institutional', 'enterprise']),
            'description' => $this->faker->sentence(),
            'price' => $this->faker->randomFloat(2, 0, 999),
            'currency' => 'USD',
            'billing_cycle' => 'monthly',
            'features' => [
                'api_calls' => $this->faker->numberBetween(100, 10000),
                'trades_per_month' => $this->faker->numberBetween(10, 1000),
                'support' => $this->faker->randomElement(['email', 'priority', '24/7']),
            ],
            'limits' => [
                'api_quota' => $this->faker->numberBetween(100, 50000),
                'trade_quota' => $this->faker->numberBetween(5, 1000),
            ],
            'active' => true,
            'meta' => null,
        ];
    }

    /**
     * Indicate that the plan is a starter plan.
     */
    public function starter(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'starter',
            'price' => 29.00,
            'features' => [
                'api_calls' => 1000,
                'trades_per_month' => 50,
                'support' => 'email',
            ],
            'limits' => [
                'api_quota' => 1000,
                'trade_quota' => 50,
            ],
        ]);
    }

    /**
     * Indicate that the plan is a professional plan.
     */
    public function professional(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'professional',
            'price' => 99.00,
            'features' => [
                'api_calls' => 5000,
                'trades_per_month' => 250,
                'support' => 'priority',
            ],
            'limits' => [
                'api_quota' => 5000,
                'trade_quota' => 250,
            ],
        ]);
    }

    /**
     * Indicate that the plan is an enterprise plan.
     */
    public function enterprise(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'enterprise',
            'price' => 299.00,
            'features' => [
                'api_calls' => 50000,
                'trades_per_month' => 1000,
                'support' => '24/7',
            ],
            'limits' => [
                'api_quota' => 50000,
                'trade_quota' => 1000,
            ],
        ]);
    }
}
