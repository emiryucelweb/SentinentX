<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\LabRun;
use PHPUnit\Framework\TestCase;

class LabRunTest extends TestCase
{
    #[Test]
    public function test_lab_run_has_correct_table_name(): void
    {
        $labRun = new LabRun;

        $this->assertSame('lab_runs', $labRun->getTable());
    }

    #[Test]
    public function test_lab_run_fillable_attributes(): void
    {
        $labRun = new LabRun;

        $expectedFillable = [
            'symbols',
            'initial_equity',
            'final_equity',
            'risk_pct',
            'max_leverage',
            'total_trades',
            'winning_trades',
            'losing_trades',
            'final_pf',
            'start_date',
            'end_date',
            'status',
            'meta',
        ];

        $this->assertSame($expectedFillable, $labRun->getFillable());
    }

    #[Test]
    public function test_lab_run_casts(): void
    {
        $labRun = new LabRun;
        $casts = $labRun->getCasts();

        $this->assertSame('array', $casts['symbols']);
        $this->assertSame('decimal:2', $casts['initial_equity']);
        $this->assertSame('decimal:2', $casts['final_equity']);
        $this->assertSame('decimal:2', $casts['risk_pct']);
        $this->assertSame('integer', $casts['max_leverage']);
        $this->assertSame('integer', $casts['total_trades']);
        $this->assertSame('integer', $casts['winning_trades']);
        $this->assertSame('integer', $casts['losing_trades']);
        $this->assertSame('decimal:6', $casts['final_pf']);
        $this->assertSame('datetime', $casts['start_date']);
        $this->assertSame('datetime', $casts['end_date']);
        $this->assertSame('array', $casts['meta']);
    }

    #[Test]
    public function test_lab_run_trading_attributes(): void
    {
        $labRun = new LabRun;

        // Core trading attributes
        $this->assertTrue(in_array('symbols', $labRun->getFillable()));
        $this->assertTrue(in_array('initial_equity', $labRun->getFillable()));
        $this->assertTrue(in_array('final_equity', $labRun->getFillable()));
    }

    #[Test]
    public function test_lab_run_risk_management_attributes(): void
    {
        $labRun = new LabRun;

        // Risk management attributes
        $this->assertTrue(in_array('risk_pct', $labRun->getFillable()));
        $this->assertTrue(in_array('max_leverage', $labRun->getFillable()));
    }

    #[Test]
    public function test_lab_run_performance_tracking(): void
    {
        $labRun = new LabRun;

        // Performance tracking attributes
        $this->assertTrue(in_array('total_trades', $labRun->getFillable()));
        $this->assertTrue(in_array('winning_trades', $labRun->getFillable()));
        $this->assertTrue(in_array('losing_trades', $labRun->getFillable()));
        $this->assertTrue(in_array('final_pf', $labRun->getFillable()));
    }

    #[Test]
    public function test_lab_run_timing_attributes(): void
    {
        $labRun = new LabRun;

        // Timing attributes
        $this->assertTrue(in_array('start_date', $labRun->getFillable()));
        $this->assertTrue(in_array('end_date', $labRun->getFillable()));
    }

    #[Test]
    public function test_lab_run_status_tracking(): void
    {
        $labRun = new LabRun;

        // Status tracking
        $this->assertTrue(in_array('status', $labRun->getFillable()));
    }

    #[Test]
    public function test_lab_run_metadata(): void
    {
        $labRun = new LabRun;

        // Metadata attributes
        $this->assertTrue(in_array('meta', $labRun->getFillable()));
    }

    #[Test]
    public function test_lab_run_has_relationship_methods(): void
    {
        $labRun = new LabRun;

        // Verify relationship methods exist
        $this->assertTrue(method_exists($labRun, 'trades'));
        $this->assertTrue(method_exists($labRun, 'metrics'));
    }

    #[Test]
    public function test_lab_run_symbols_array_cast(): void
    {
        $labRun = new LabRun;

        // Symbols should be array for multiple symbols
        $this->assertSame('array', $labRun->getCasts()['symbols']);
    }

    #[Test]
    public function test_lab_run_equity_decimal_casts(): void
    {
        $labRun = new LabRun;

        // Equity fields should have 2 decimal precision
        $this->assertSame('decimal:2', $labRun->getCasts()['initial_equity']);
        $this->assertSame('decimal:2', $labRun->getCasts()['final_equity']);
    }

    #[Test]
    public function test_lab_run_risk_decimal_cast(): void
    {
        $labRun = new LabRun;

        // Risk percentage should have 2 decimal precision
        $this->assertSame('decimal:2', $labRun->getCasts()['risk_pct']);
    }

    #[Test]
    public function test_lab_run_leverage_integer_cast(): void
    {
        $labRun = new LabRun;

        // Max leverage should be integer
        $this->assertSame('integer', $labRun->getCasts()['max_leverage']);
    }

    #[Test]
    public function test_lab_run_trade_count_casts(): void
    {
        $labRun = new LabRun;

        // Trade count fields should be integers
        $this->assertSame('integer', $labRun->getCasts()['total_trades']);
        $this->assertSame('integer', $labRun->getCasts()['winning_trades']);
        $this->assertSame('integer', $labRun->getCasts()['losing_trades']);
    }

    #[Test]
    public function test_lab_run_profit_factor_cast(): void
    {
        $labRun = new LabRun;

        // Profit factor should have 6 decimal precision
        $this->assertSame('decimal:6', $labRun->getCasts()['final_pf']);
    }

    #[Test]
    public function test_lab_run_datetime_fields(): void
    {
        $labRun = new LabRun;
        $casts = $labRun->getCasts();

        // Time fields should be datetime
        $timeFields = ['start_date', 'end_date'];

        foreach ($timeFields as $field) {
            $this->assertSame('datetime', $casts[$field], "Field {$field} should be datetime");
        }
    }

    #[Test]
    public function test_lab_run_meta_array_cast(): void
    {
        $labRun = new LabRun;

        // Meta should be array for extensibility
        $this->assertSame('array', $labRun->getCasts()['meta']);
    }

    #[Test]
    public function test_lab_run_lab_environment_ready(): void
    {
        $labRun = new LabRun;

        // Lab environment essential fields
        $fillable = $labRun->getFillable();

        // Symbol selection
        $this->assertTrue(in_array('symbols', $fillable));

        // Risk parameters
        $this->assertTrue(in_array('risk_pct', $fillable));
        $this->assertTrue(in_array('max_leverage', $fillable));
    }

    #[Test]
    public function test_lab_run_performance_analysis_ready(): void
    {
        $labRun = new LabRun;

        // Performance analysis essential fields
        $fillable = $labRun->getFillable();

        // Equity tracking
        $this->assertTrue(in_array('initial_equity', $fillable));
        $this->assertTrue(in_array('final_equity', $fillable));

        // Trade statistics
        $this->assertTrue(in_array('total_trades', $fillable));
        $this->assertTrue(in_array('winning_trades', $fillable));
        $this->assertTrue(in_array('losing_trades', $fillable));

        // Performance metrics
        $this->assertTrue(in_array('final_pf', $fillable));
    }

    #[Test]
    public function test_lab_run_risk_management_ready(): void
    {
        $labRun = new LabRun;

        // Risk management essential fields
        $fillable = $labRun->getFillable();

        // Risk controls
        $this->assertTrue(in_array('risk_pct', $fillable));
        $this->assertTrue(in_array('max_leverage', $fillable));
    }

    #[Test]
    public function test_lab_run_analytics_ready(): void
    {
        $labRun = new LabRun;

        // Analytics essential fields
        $fillable = $labRun->getFillable();

        // Performance metrics
        $this->assertTrue(in_array('final_pf', $fillable));

        // Trade analysis
        $this->assertTrue(in_array('total_trades', $fillable));
        $this->assertTrue(in_array('winning_trades', $fillable));
        $this->assertTrue(in_array('losing_trades', $fillable));

        // Metadata
        $this->assertTrue(in_array('meta', $fillable));
    }

    #[Test]
    public function test_lab_run_model_structure(): void
    {
        $labRun = new LabRun;

        // Verify model structure
        $reflection = new \ReflectionClass($labRun);

        $this->assertTrue($reflection->isFinal()); // LabRun should be immutable
        $this->assertTrue($reflection->isSubclassOf(\Illuminate\Database\Eloquent\Model::class));

        // Verify HasFactory trait
        $this->assertTrue(in_array('Illuminate\Database\Eloquent\Factories\HasFactory', class_uses($labRun)));
    }

    #[Test]
    public function test_lab_run_saas_ready(): void
    {
        $labRun = new LabRun;

        // SaaS essential fields
        $fillable = $labRun->getFillable();

        // Performance tracking for billing
        $this->assertTrue(in_array('final_pf', $fillable));

        // Usage tracking
        $this->assertTrue(in_array('total_trades', $fillable));
        $this->assertTrue(in_array('start_date', $fillable));
        $this->assertTrue(in_array('end_date', $fillable));
    }

    #[Test]
    public function test_lab_run_data_integrity(): void
    {
        $labRun = new LabRun;

        // Data integrity checks
        $fillable = $labRun->getFillable();
        $casts = $labRun->getCasts();

        // All fillable fields should have corresponding casts where needed
        $this->assertTrue(in_array('symbols', array_keys($casts)));
        $this->assertTrue(in_array('initial_equity', array_keys($casts)));
        $this->assertTrue(in_array('final_equity', array_keys($casts)));
        $this->assertTrue(in_array('risk_pct', array_keys($casts)));
        $this->assertTrue(in_array('max_leverage', array_keys($casts)));
        $this->assertTrue(in_array('total_trades', array_keys($casts)));
        $this->assertTrue(in_array('winning_trades', array_keys($casts)));
        $this->assertTrue(in_array('losing_trades', array_keys($casts)));
        $this->assertTrue(in_array('final_pf', array_keys($casts)));
        $this->assertTrue(in_array('start_date', array_keys($casts)));
        $this->assertTrue(in_array('end_date', array_keys($casts)));
        $this->assertTrue(in_array('meta', array_keys($casts)));
    }

    #[Test]
    public function test_lab_run_extensibility(): void
    {
        $labRun = new LabRun;

        // Extensibility through meta field
        $this->assertTrue(in_array('meta', $labRun->getFillable()));
        $this->assertSame('array', $labRun->getCasts()['meta']);
    }
}
