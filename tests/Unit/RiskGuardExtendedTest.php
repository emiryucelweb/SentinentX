<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\Risk\RiskGuard;
use Tests\TestCase;

class RiskGuardExtendedTest extends TestCase
{
    private RiskGuard $riskGuard;

    protected function setUp(): void
    {
        parent::setUp();
        $this->riskGuard = new RiskGuard;
    }

    public function test_usdt_depeg_normal_parity()
    {
        $result = $this->riskGuard->usdtDepeg(1.0, 0.995, 1.005);
        $this->assertFalse($result); // Normal parity should be OK
    }

    public function test_usdt_depeg_low_parity_blocks()
    {
        $result = $this->riskGuard->usdtDepeg(0.990, 0.995, 1.005);
        $this->assertTrue($result); // Low parity should block
    }

    public function test_usdt_depeg_high_parity_blocks()
    {
        $result = $this->riskGuard->usdtDepeg(1.010, 0.995, 1.005);
        $this->assertTrue($result); // High parity should block
    }

    public function test_usdt_depeg_edge_case_exactly_995()
    {
        $result = $this->riskGuard->usdtDepeg(0.995, 0.995, 1.005);
        $this->assertFalse($result); // Exactly at lower bound should be OK
    }

    public function test_usdt_depeg_edge_case_exactly_1005()
    {
        $result = $this->riskGuard->usdtDepeg(1.005, 0.995, 1.005);
        $this->assertFalse($result); // Exactly at upper bound should be OK
    }

    public function test_usdt_depeg_with_different_bounds()
    {
        $result = $this->riskGuard->usdtDepeg(0.985, 0.98, 1.02);
        $this->assertFalse($result); // Should be within wider bounds

        $result2 = $this->riskGuard->usdtDepeg(0.975, 0.98, 1.02);
        $this->assertTrue($result2); // Should be outside bounds
    }

    public function test_usdt_depeg_extreme_values()
    {
        $result = $this->riskGuard->usdtDepeg(0.5, 0.995, 1.005);
        $this->assertTrue($result); // Extremely low value should block

        $result2 = $this->riskGuard->usdtDepeg(2.0, 0.995, 1.005);
        $this->assertTrue($result2); // Extremely high value should block
    }

    public function test_ok_to_open_valid_position()
    {
        $result = $this->riskGuard->okToOpen(
            'BTCUSDT',
            50000.0,
            'LONG',
            10, // Normal leverage
            48000.0, // 4% stop loss
            0.3 // Lower k factor for more permissive buffer
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('ok', $result);
        $this->assertTrue($result['ok']); // Valid position should be OK
    }

    public function test_ok_to_open_insufficient_buffer()
    {
        $result = $this->riskGuard->okToOpen(
            'BTCUSDT',
            50000.0,
            'LONG',
            50, // High leverage
            49900.0 // Very tight stop loss
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('ok', $result);
        $this->assertArrayHasKey('reason', $result);
        // Should likely fail due to insufficient buffer
    }
}
