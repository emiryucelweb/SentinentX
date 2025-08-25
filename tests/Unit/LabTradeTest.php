<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\LabTrade;
use PHPUnit\Framework\TestCase;

class LabTradeTest extends TestCase
{
    #[Test]
    public function test_lab_trade_has_correct_table_name(): void
    {
        $labTrade = new LabTrade;

        $this->assertSame('lab_trades', $labTrade->getTable());
    }

    #[Test]
    public function test_lab_trade_fillable_attributes(): void
    {
        $labTrade = new LabTrade;

        $expectedFillable = [
            'symbol', 'side', 'qty', 'entry_price', 'exit_price', 'opened_at',
            'closed_at', 'pnl_quote', 'pnl_pct', 'cycle_uuid', 'meta',
        ];

        $this->assertSame($expectedFillable, $labTrade->getFillable());
    }

    #[Test]
    public function test_lab_trade_casts(): void
    {
        $labTrade = new LabTrade;
        $casts = $labTrade->getCasts();

        $this->assertSame('datetime', $casts['opened_at']);
        $this->assertSame('datetime', $casts['closed_at']);
        $this->assertSame('array', $casts['meta']);
    }

    #[Test]
    public function test_lab_trade_trading_attributes(): void
    {
        $labTrade = new LabTrade;

        // Core trading attributes
        $this->assertTrue(in_array('symbol', $labTrade->getFillable()));
        $this->assertTrue(in_array('side', $labTrade->getFillable()));
        $this->assertTrue(in_array('qty', $labTrade->getFillable()));
        $this->assertTrue(in_array('entry_price', $labTrade->getFillable()));
        $this->assertTrue(in_array('exit_price', $labTrade->getFillable()));
    }

    #[Test]
    public function test_lab_trade_timing_attributes(): void
    {
        $labTrade = new LabTrade;

        // Timing attributes
        $this->assertTrue(in_array('opened_at', $labTrade->getFillable()));
        $this->assertTrue(in_array('closed_at', $labTrade->getFillable()));
    }

    #[Test]
    public function test_lab_trade_pnl_tracking(): void
    {
        $labTrade = new LabTrade;

        // PnL tracking attributes
        $this->assertTrue(in_array('pnl_quote', $labTrade->getFillable()));
        $this->assertTrue(in_array('pnl_pct', $labTrade->getFillable()));
    }

    #[Test]
    public function test_lab_trade_cycle_tracking(): void
    {
        $labTrade = new LabTrade;

        // Cycle tracking
        $this->assertTrue(in_array('cycle_uuid', $labTrade->getFillable()));
    }

    #[Test]
    public function test_lab_trade_metadata(): void
    {
        $labTrade = new LabTrade;

        // Metadata attributes
        $this->assertTrue(in_array('meta', $labTrade->getFillable()));
    }

    #[Test]
    public function test_lab_trade_datetime_fields(): void
    {
        $labTrade = new LabTrade;
        $casts = $labTrade->getCasts();

        // Time fields should be datetime
        $timeFields = ['opened_at', 'closed_at'];

        foreach ($timeFields as $field) {
            $this->assertSame('datetime', $casts[$field], "Field {$field} should be datetime");
        }
    }

    #[Test]
    public function test_lab_trade_meta_array_cast(): void
    {
        $labTrade = new LabTrade;

        // Meta should be array for extensibility
        $this->assertSame('array', $labTrade->getCasts()['meta']);
    }

    #[Test]
    public function test_lab_trade_lab_environment_ready(): void
    {
        $labTrade = new LabTrade;

        // Lab environment essential fields
        $fillable = $labTrade->getFillable();

        // Trade identification
        $this->assertTrue(in_array('symbol', $fillable));
        $this->assertTrue(in_array('side', $fillable));

        // Cycle tracking
        $this->assertTrue(in_array('cycle_uuid', $fillable));
    }

    #[Test]
    public function test_lab_trade_performance_tracking_ready(): void
    {
        $labTrade = new LabTrade;

        // Performance tracking essential fields
        $fillable = $labTrade->getFillable();

        // PnL metrics
        $this->assertTrue(in_array('pnl_quote', $fillable));
        $this->assertTrue(in_array('pnl_pct', $fillable));

        // Timing
        $this->assertTrue(in_array('opened_at', $fillable));
        $this->assertTrue(in_array('closed_at', $fillable));
    }

    #[Test]
    public function test_lab_trade_risk_management_ready(): void
    {
        $labTrade = new LabTrade;

        // Risk management essential fields
        $fillable = $labTrade->getFillable();

        // Position sizing
        $this->assertTrue(in_array('qty', $fillable));

        // Price tracking
        $this->assertTrue(in_array('entry_price', $fillable));
        $this->assertTrue(in_array('exit_price', $fillable));
    }

    #[Test]
    public function test_lab_trade_analytics_ready(): void
    {
        $labTrade = new LabTrade;

        // Analytics essential fields
        $fillable = $labTrade->getFillable();

        // Performance metrics
        $this->assertTrue(in_array('pnl_quote', $fillable));
        $this->assertTrue(in_array('pnl_pct', $fillable));

        // Trade metadata
        $this->assertTrue(in_array('meta', $fillable));
    }

    #[Test]
    public function test_lab_trade_model_structure(): void
    {
        $labTrade = new LabTrade;

        // Verify model structure
        $reflection = new \ReflectionClass($labTrade);

        $this->assertTrue($reflection->isFinal()); // LabTrade should be immutable
        $this->assertTrue($reflection->isSubclassOf(\Illuminate\Database\Eloquent\Model::class));

        // Verify HasFactory trait
        $this->assertTrue(in_array('Illuminate\Database\Eloquent\Factories\HasFactory', class_uses($labTrade)));
    }

    #[Test]
    public function test_lab_trade_saas_ready(): void
    {
        $labTrade = new LabTrade;

        // SaaS essential fields
        $fillable = $labTrade->getFillable();

        // Performance tracking for billing
        $this->assertTrue(in_array('pnl_quote', $fillable));
        $this->assertTrue(in_array('pnl_pct', $fillable));

        // Usage tracking
        $this->assertTrue(in_array('opened_at', $fillable));
        $this->assertTrue(in_array('closed_at', $fillable));
    }

    #[Test]
    public function test_lab_trade_data_integrity(): void
    {
        $labTrade = new LabTrade;

        // Data integrity checks
        $fillable = $labTrade->getFillable();
        $casts = $labTrade->getCasts();

        // All fillable fields should have corresponding casts where needed
        $this->assertTrue(in_array('opened_at', array_keys($casts)));
        $this->assertTrue(in_array('closed_at', array_keys($casts)));
        $this->assertTrue(in_array('meta', array_keys($casts)));
    }

    #[Test]
    public function test_lab_trade_extensibility(): void
    {
        $labTrade = new LabTrade;

        // Extensibility through meta field
        $this->assertTrue(in_array('meta', $labTrade->getFillable()));
        $this->assertSame('array', $labTrade->getCasts()['meta']);
    }
}
