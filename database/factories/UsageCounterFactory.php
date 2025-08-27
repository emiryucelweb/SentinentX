<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\UsageCounter;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UsageCounter>
 */
class UsageCounterFactory extends Factory
{
    protected $model = UsageCounter::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'service' => $this->faker->randomElement(['api_requests', 'trades', 'data_export']),
            'count' => $this->faker->numberBetween(0, 1000),
            'period' => 'monthly',
            'reset_at' => now()->endOfMonth(),
        ];
    }

    /**
     * Indicate that the usage counter is for API requests.
     */
    public function apiRequests(): static
    {
        return $this->state(fn (array $attributes) => [
            'service' => 'api_requests',
        ]);
    }

    /**
     * Indicate that the usage counter is for trades.
     */
    public function trades(): static
    {
        return $this->state(fn (array $attributes) => [
            'service' => 'trades',
        ]);
    }

    /**
     * Set a specific usage count.
     */
    public function withCount(int $count): static
    {
        return $this->state(fn (array $attributes) => [
            'count' => $count,
        ]);
    }

    /**
     * Set a specific period.
     */
    public function withPeriod(string $period): static
    {
        return $this->state(fn (array $attributes) => [
            'period' => $period,
        ]);
    }
}
