<?php

namespace Database\Factories;

use App\Models\Trade;
use Illuminate\Database\Eloquent\Factories\Factory;

class TradeFactory extends Factory
{
    protected $model = Trade::class;

    public function definition(): array
    {
        $symbols = ['BTCUSDT', 'ETHUSDT', 'ADAUSDT', 'SOLUSDT', 'DOTUSDT'];
        $sides = ['LONG', 'SHORT'];
        $statuses = ['OPEN', 'CLOSED', 'CANCELLED'];

        $side = $this->faker->randomElement($sides);
        $entryPrice = $this->faker->randomFloat(2, 1000, 70000);
        $qty = $this->faker->randomFloat(4, 0.001, 10);
        $pnl = $this->faker->randomFloat(2, -2000, 3000);

        return [
            'symbol' => $this->faker->randomElement($symbols),
            'side' => $side,
            'status' => $this->faker->randomElement($statuses),
            'margin_mode' => 'ISOLATED',
            'leverage' => $this->faker->numberBetween(1, 20),
            'qty' => $qty,
            'entry_price' => $entryPrice,
            'take_profit' => $entryPrice * ($side === 'LONG' ? 1.05 : 0.95),
            'stop_loss' => $entryPrice * ($side === 'LONG' ? 0.98 : 1.02),
            'pnl' => $pnl,
            'pnl_realized' => $this->faker->randomFloat(2, -1000, 2000),
            'fees_total' => $this->faker->randomFloat(2, 5, 50),
            'bybit_order_id' => $this->faker->uuid(),
            'opened_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
            'closed_at' => $this->faker->optional(0.7)->dateTimeBetween('-30 days', 'now'),
            'meta' => json_encode([
                'source' => 'E2E_TEST',
                'confidence' => $this->faker->numberBetween(80, 99),
            ]),
            'created_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
            'updated_at' => now(),
        ];
    }

    /**
     * Indicate that the trade is open
     */
    public function open(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'OPEN',
            'closed_at' => null,
            'pnl' => 0,
        ]);
    }

    /**
     * Indicate that the trade is closed
     */
    public function closed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'CLOSED',
            'closed_at' => now(),
        ]);
    }

    /**
     * Create a profitable trade
     */
    public function profitable(): static
    {
        return $this->state(fn (array $attributes) => [
            'pnl' => $this->faker->randomFloat(2, 100, 2000),
            'status' => 'CLOSED',
            'closed_at' => now(),
        ]);
    }

    /**
     * Create a losing trade
     */
    public function losing(): static
    {
        return $this->state(fn (array $attributes) => [
            'pnl' => $this->faker->randomFloat(2, -2000, -100),
            'status' => 'CLOSED',
            'closed_at' => now(),
        ]);
    }
}
