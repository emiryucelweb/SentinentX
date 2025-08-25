<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\Trading\PositionSizer;
use Tests\TestCase;

class PositionSizerExtendedTest extends TestCase
{
    private PositionSizer $positionSizer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->positionSizer = new PositionSizer;
    }

    public function test_size_by_im_cap_low_risk_band()
    {
        $result = $this->positionSizer->sizeByImCap(
            equity: 10000.0,
            marginUtilization: 0.2, // Low risk
            freeCollateral: 8000.0,
            leverage: 10.0,
            price: 50000.0,
            qtyStep: 0.001,
            minQty: 0.001
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('qty', $result);
        $this->assertArrayHasKey('risk_band', $result);
        $this->assertEquals('low', $result['risk_band']);
        $this->assertGreaterThan(0, $result['qty']);
    }

    public function test_size_by_im_cap_medium_risk_band()
    {
        $result = $this->positionSizer->sizeByImCap(
            equity: 10000.0,
            marginUtilization: 0.5, // Medium risk
            freeCollateral: 5000.0,
            leverage: 15.0,
            price: 50000.0
        );

        $this->assertIsArray($result);
        $this->assertEquals('medium', $result['risk_band']);
        $this->assertGreaterThanOrEqual(0, $result['qty']);
    }

    public function test_size_by_im_cap_high_risk_band()
    {
        $result = $this->positionSizer->sizeByImCap(
            equity: 10000.0,
            marginUtilization: 0.75, // High risk
            freeCollateral: 2000.0,
            leverage: 20.0,
            price: 50000.0
        );

        $this->assertIsArray($result);
        $this->assertEquals('high', $result['risk_band']);
        $this->assertGreaterThanOrEqual(0, $result['qty']);
    }

    public function test_qty_step_and_min_qty_enforcement()
    {
        $result = $this->positionSizer->sizeByImCap(
            equity: 10000.0,
            marginUtilization: 0.3,
            freeCollateral: 7000.0,
            leverage: 5.0,
            price: 50000.0,
            qtyStep: 0.01,
            minQty: 0.05
        );

        $this->assertIsArray($result);
        $this->assertGreaterThanOrEqual(0.05, $result['qty']); // Min qty enforced

        // Qty should be multiple of step (0.01) - with floating point tolerance
        $this->assertLessThan(0.0001, fmod($result['qty'], 0.01));
    }

    public function test_band_calculation_accuracy()
    {
        // Test boundary conditions for risk bands
        $lowRisk = $this->positionSizer->sizeByImCap(
            equity: 10000.0,
            marginUtilization: 0.29, // Just below medium threshold
            freeCollateral: 7000.0,
            leverage: 10.0,
            price: 50000.0
        );

        $mediumRisk = $this->positionSizer->sizeByImCap(
            equity: 10000.0,
            marginUtilization: 0.31, // Just above low threshold
            freeCollateral: 7000.0,
            leverage: 10.0,
            price: 50000.0
        );

        $this->assertEquals('low', $lowRisk['risk_band']);
        $this->assertEquals('medium', $mediumRisk['risk_band']);
    }
}
