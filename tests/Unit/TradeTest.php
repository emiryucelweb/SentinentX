<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Trade;
use PHPUnit\Framework\TestCase;

class TradeTest extends TestCase
{
    #[Test]
    public function test_trade_has_correct_fillable_attributes(): void
    {
        $trade = new Trade;

        $expectedFillable = [
            'tenant_id', 'symbol', 'side', 'status', 'margin_mode', 'leverage', 'qty', 'entry_price',
            'take_profit', 'stop_loss', 'pnl', 'pnl_realized', 'fees_total',
            'bybit_order_id', 'opened_at', 'closed_at', 'meta',
        ];

        $this->assertSame($expectedFillable, $trade->getFillable());
    }

    #[Test]
    public function test_trade_casts_are_correct(): void
    {
        $trade = new Trade;
        $casts = $trade->getCasts();

        // Decimal casts for financial precision
        $this->assertSame('decimal:8', $casts['qty']);
        $this->assertSame('decimal:8', $casts['entry_price']);
        $this->assertSame('decimal:8', $casts['take_profit']);
        $this->assertSame('decimal:8', $casts['stop_loss']);
        $this->assertSame('decimal:8', $casts['pnl']);
        $this->assertSame('decimal:8', $casts['pnl_realized']);
        $this->assertSame('decimal:8', $casts['fees_total']);

        // Datetime casts
        $this->assertSame('datetime', $casts['opened_at']);
        $this->assertSame('datetime', $casts['closed_at']);

        // Array cast for metadata
        $this->assertSame('array', $casts['meta']);
    }

    #[Test]
    public function test_trade_trading_attributes(): void
    {
        $trade = new Trade;

        // Core trading attributes
        $this->assertTrue(in_array('symbol', $trade->getFillable()));
        $this->assertTrue(in_array('side', $trade->getFillable()));
        $this->assertTrue(in_array('status', $trade->getFillable()));
        $this->assertTrue(in_array('entry_price', $trade->getFillable()));
        $this->assertTrue(in_array('take_profit', $trade->getFillable()));
        $this->assertTrue(in_array('stop_loss', $trade->getFillable()));
    }

    #[Test]
    public function test_trade_risk_management_attributes(): void
    {
        $trade = new Trade;

        // Risk management attributes
        $this->assertTrue(in_array('margin_mode', $trade->getFillable()));
        $this->assertTrue(in_array('leverage', $trade->getFillable()));
        $this->assertTrue(in_array('qty', $trade->getFillable()));
    }

    #[Test]
    public function test_trade_pnl_tracking_attributes(): void
    {
        $trade = new Trade;

        // PnL tracking attributes
        $this->assertTrue(in_array('pnl', $trade->getFillable()));
        $this->assertTrue(in_array('pnl_realized', $trade->getFillable()));
        $this->assertTrue(in_array('fees_total', $trade->getFillable()));
    }

    #[Test]
    public function test_trade_exchange_integration_attributes(): void
    {
        $trade = new Trade;

        // Exchange integration attributes
        $this->assertTrue(in_array('bybit_order_id', $trade->getFillable()));
    }

    #[Test]
    public function test_trade_timing_attributes(): void
    {
        $trade = new Trade;

        // Timing attributes
        $this->assertTrue(in_array('opened_at', $trade->getFillable()));
        $this->assertTrue(in_array('closed_at', $trade->getFillable()));
    }

    #[Test]
    public function test_trade_metadata_attributes(): void
    {
        $trade = new Trade;

        // Metadata attributes
        $this->assertTrue(in_array('meta', $trade->getFillable()));
    }

    #[Test]
    public function test_trade_decimal_precision(): void
    {
        $trade = new Trade;
        $casts = $trade->getCasts();

        // All financial values should have 8 decimal precision
        $financialFields = ['qty', 'entry_price', 'take_profit', 'stop_loss', 'pnl', 'pnl_realized', 'fees_total'];

        foreach ($financialFields as $field) {
            $this->assertSame('decimal:8', $casts[$field], "Field {$field} should have 8 decimal precision");
        }
    }

    #[Test]
    public function test_trade_datetime_fields(): void
    {
        $trade = new Trade;
        $casts = $trade->getCasts();

        // Time fields should be datetime
        $timeFields = ['opened_at', 'closed_at'];

        foreach ($timeFields as $field) {
            $this->assertSame('datetime', $casts[$field], "Field {$field} should be datetime");
        }
    }

    #[Test]
    public function test_trade_saas_billing_ready(): void
    {
        $trade = new Trade;

        // SaaS billing essential fields
        $fillable = $trade->getFillable();

        // PnL tracking for billing
        $this->assertTrue(in_array('pnl', $fillable));
        $this->assertTrue(in_array('pnl_realized', $fillable));
        $this->assertTrue(in_array('fees_total', $fillable));

        // Trade lifecycle for usage tracking
        $this->assertTrue(in_array('opened_at', $fillable));
        $this->assertTrue(in_array('closed_at', $fillable));
    }

    #[Test]
    public function test_trade_risk_management_ready(): void
    {
        $trade = new Trade;

        // Risk management essential fields
        $fillable = $trade->getFillable();

        // Position sizing
        $this->assertTrue(in_array('qty', $fillable));
        $this->assertTrue(in_array('leverage', $fillable));

        // Risk controls
        $this->assertTrue(in_array('take_profit', $fillable));
        $this->assertTrue(in_array('stop_loss', $fillable));

        // Margin management
        $this->assertTrue(in_array('margin_mode', $fillable));
    }

    #[Test]
    public function test_trade_exchange_integration_ready(): void
    {
        $trade = new Trade;

        // Exchange integration essential fields
        $fillable = $trade->getFillable();

        // Order tracking
        $this->assertTrue(in_array('bybit_order_id', $fillable));

        // Trade identification
        $this->assertTrue(in_array('symbol', $fillable));
        $this->assertTrue(in_array('side', $fillable));
        $this->assertTrue(in_array('status', $fillable));
    }

    #[Test]
    public function test_trade_analytics_ready(): void
    {
        $trade = new Trade;

        // Analytics essential fields
        $fillable = $trade->getFillable();

        // Performance metrics
        $this->assertTrue(in_array('pnl', $fillable));
        $this->assertTrue(in_array('pnl_realized', $fillable));
        $this->assertTrue(in_array('fees_total', $fillable));

        // Trade metadata
        $this->assertTrue(in_array('meta', $fillable));
    }

    #[Test]
    public function test_trade_model_structure(): void
    {
        $trade = new Trade;

        // Verify model structure
        $reflection = new \ReflectionClass($trade);

        $this->assertFalse($reflection->isFinal()); // Model should be extensible
        $this->assertTrue($reflection->isSubclassOf(\Illuminate\Database\Eloquent\Model::class));
    }
}
