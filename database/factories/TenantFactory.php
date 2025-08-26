<?php

namespace Database\Factories;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class TenantFactory extends Factory
{
    protected $model = Tenant::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->company(),
            'domain' => $this->faker->domainName(),
            'database' => null,
            'settings' => json_encode([
                'plan' => $this->faker->randomElement(['starter', 'pro', 'enterprise']),
                'limits' => [
                    'max_trades_per_day' => $this->faker->numberBetween(10, 1000),
                    'max_positions' => $this->faker->numberBetween(5, 50),
                    'max_api_calls_per_minute' => $this->faker->numberBetween(60, 600),
                ],
            ]),
            'active' => true,
            'meta' => null,
        ];
    }
}
