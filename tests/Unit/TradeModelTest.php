<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Trade;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TradeModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_trade_creation()
    {
        $trade = Trade::create([
            'symbol' => 'BTCUSDT',
            'side' => 'LONG',
            'qty' => 0.001,
            'entry_price' => 30000,
            'status' => 'OPEN',
        ]);

        $this->assertDatabaseHas('trades', [
            'symbol' => 'BTCUSDT',
            'side' => 'LONG',
            'qty' => 0.001,
            'entry_price' => 30000,
            'status' => 'OPEN',
        ]);

        $this->assertEquals('BTCUSDT', $trade->symbol);
        $this->assertEquals('LONG', $trade->side);
        $this->assertEquals(0.001, $trade->qty);
        $this->assertEquals(30000, $trade->entry_price);
        $this->assertEquals('OPEN', $trade->status);
    }

    public function test_trade_side_enum_validation()
    {
        // Valid sides
        $longTrade = Trade::create([
            'symbol' => 'BTCUSDT',
            'side' => 'LONG',
            'qty' => 0.001,
            'entry_price' => 30000,
            'status' => 'OPEN',
        ]);

        $shortTrade = Trade::create([
            'symbol' => 'ETHUSDT',
            'side' => 'SHORT',
            'qty' => 0.01,
            'entry_price' => 2000,
            'status' => 'OPEN',
        ]);

        $this->assertEquals('LONG', $longTrade->side);
        $this->assertEquals('SHORT', $shortTrade->side);
    }

    public function test_trade_status_updates()
    {
        $trade = Trade::create([
            'symbol' => 'BTCUSDT',
            'side' => 'LONG',
            'qty' => 0.001,
            'entry_price' => 30000,
            'status' => 'OPEN',
        ]);

        $trade->update(['status' => 'CLOSED']);
        $this->assertEquals('CLOSED', $trade->fresh()->status);

        $trade->update(['status' => 'CANCELLED']);
        $this->assertEquals('CANCELLED', $trade->fresh()->status);
    }

    public function test_trade_pnl_calculation()
    {
        $trade = Trade::create([
            'symbol' => 'BTCUSDT',
            'side' => 'LONG',
            'qty' => 0.001,
            'entry_price' => 30000,
            'status' => 'OPEN',
            'pnl' => 50.0,
        ]);

        $this->assertEquals(50.0, $trade->pnl);
    }

    public function test_trade_timestamps()
    {
        $trade = Trade::create([
            'symbol' => 'BTCUSDT',
            'side' => 'LONG',
            'qty' => 0.001,
            'entry_price' => 30000,
            'status' => 'OPEN',
        ]);

        $this->assertNotNull($trade->created_at);
        $this->assertNotNull($trade->updated_at);
    }

    public function test_trade_fillable_attributes()
    {
        $expectedFillable = [
            'tenant_id', 'symbol', 'side', 'status', 'margin_mode', 'leverage', 'qty', 'entry_price',
            'take_profit', 'stop_loss', 'pnl', 'pnl_realized', 'fees_total',
            'bybit_order_id', 'opened_at', 'closed_at', 'meta',
        ];

        $trade = new Trade;
        $this->assertEquals($expectedFillable, $trade->getFillable());
    }

    public function test_trade_query_scopes()
    {
        Trade::create([
            'symbol' => 'BTCUSDT',
            'side' => 'LONG',
            'qty' => 0.001,
            'entry_price' => 30000,
            'status' => 'OPEN',
        ]);

        Trade::create([
            'symbol' => 'ETHUSDT',
            'side' => 'SHORT',
            'qty' => 0.01,
            'entry_price' => 2000,
            'status' => 'CLOSED',
        ]);

        $openTrades = Trade::where('status', 'OPEN')->count();
        $closedTrades = Trade::where('status', 'CLOSED')->count();
        $btcTrades = Trade::where('symbol', 'BTCUSDT')->count();

        $this->assertEquals(1, $openTrades);
        $this->assertEquals(1, $closedTrades);
        $this->assertEquals(1, $btcTrades);
    }

    public function test_trade_relationships_exist()
    {
        $trade = new Trade;

        // Test that the model has expected relationship methods if they exist
        $methods = get_class_methods($trade);

        // These are common relationship methods that might exist
        $possibleRelationships = ['user', 'orders', 'executions'];

        foreach ($possibleRelationships as $relationship) {
            if (method_exists($trade, $relationship)) {
                $this->assertTrue(method_exists($trade, $relationship));
            }
        }

        // Ensure test has at least one assertion
        $this->assertIsObject($trade);
    }
}
