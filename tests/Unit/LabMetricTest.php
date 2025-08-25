<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\LabMetric;
use PHPUnit\Framework\TestCase;

class LabMetricTest extends TestCase
{
    #[Test]
    public function test_lab_metric_has_correct_table_name(): void
    {
        $labMetric = new LabMetric;

        $this->assertSame('lab_metrics', $labMetric->getTable());
    }

    #[Test]
    public function test_lab_metric_fillable_attributes(): void
    {
        $labMetric = new LabMetric;

        $expectedFillable = ['as_of', 'pf', 'maxdd_pct', 'sharpe', 'meta'];

        $this->assertSame($expectedFillable, $labMetric->getFillable());
    }

    #[Test]
    public function test_lab_metric_casts(): void
    {
        $labMetric = new LabMetric;

        $this->assertSame('date', $labMetric->getCasts()['as_of']);
        $this->assertSame('array', $labMetric->getCasts()['meta']);
    }

    #[Test]
    public function test_lab_metric_trading_performance_fields(): void
    {
        $labMetric = new LabMetric;

        // Trading performance metrics
        $this->assertTrue(in_array('pf', $labMetric->getFillable())); // Profit Factor
        $this->assertTrue(in_array('maxdd_pct', $labMetric->getFillable())); // Max Drawdown %
        $this->assertTrue(in_array('sharpe', $labMetric->getFillable())); // Sharpe Ratio
        $this->assertTrue(in_array('as_of', $labMetric->getFillable())); // Date
    }

    #[Test]
    public function test_lab_metric_date_field_cast(): void
    {
        $labMetric = new LabMetric;

        // Date field for time series analysis
        $this->assertSame('date', $labMetric->getCasts()['as_of']);
    }

    #[Test]
    public function test_lab_metric_meta_extensibility(): void
    {
        $labMetric = new LabMetric;

        // Meta field for additional metrics
        $this->assertTrue(in_array('meta', $labMetric->getFillable()));
        $this->assertSame('array', $labMetric->getCasts()['meta']);
    }

    #[Test]
    public function test_lab_metric_model_structure(): void
    {
        $labMetric = new LabMetric;

        // Verify model is final
        $reflection = new \ReflectionClass($labMetric);
        $this->assertTrue($reflection->isFinal());

        // Verify HasFactory trait
        $this->assertTrue(in_array('Illuminate\Database\Eloquent\Factories\HasFactory', class_uses($labMetric)));
    }

    #[Test]
    public function test_lab_metric_saas_analytics_ready(): void
    {
        $labMetric = new LabMetric;

        // SaaS analytics essential fields
        $fillable = $labMetric->getFillable();

        // Performance metrics for dashboard
        $this->assertTrue(in_array('pf', $fillable));
        $this->assertTrue(in_array('maxdd_pct', $fillable));
        $this->assertTrue(in_array('sharpe', $fillable));

        // Time dimension
        $this->assertTrue(in_array('as_of', $fillable));

        // Extensibility
        $this->assertTrue(in_array('meta', $fillable));
    }

    #[Test]
    public function test_lab_metric_key_performance_indicators(): void
    {
        $labMetric = new LabMetric;

        // Key trading KPIs
        $expectedKpis = ['pf', 'maxdd_pct', 'sharpe'];

        foreach ($expectedKpis as $kpi) {
            $this->assertTrue(in_array($kpi, $labMetric->getFillable()), "KPI {$kpi} should be fillable");
        }
    }
}
