<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Services\Exchange\InstrumentInfoService;
use App\Services\Risk\RiskGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class RiskGuardTest extends TestCase
{
    use RefreshDatabase;

    private function guard(): RiskGuard
    {
        $info = $this->app->make(InstrumentInfoService::class);

        return new RiskGuard($info);
    }

    public function test_ok_to_open_rejects_if_sl_too_close_to_liquidation(): void
    {
        // 60x kaldıraç → ~%1.66 likidasyon bandı, k=1.2 ⇒ min ~%2
        $g = $this->guard();
        $entry = 64000.0;
        $slTooClose = 62900.0; // ~ -1.72% (k=1.2 ile sınır ~2% → reddet)
        $result = $g->okToOpen('BTCUSDT', $entry, 'LONG', 60, $slTooClose);
        $this->assertFalse($result['ok']);
        $this->assertEquals('LIQ_BUFFER_INSUFFICIENT', $result['reason']);
        $this->assertArrayHasKey('details', $result);
        $this->assertArrayHasKey('distance_pct', $result['details']);

        $slSafe = 62700.0; // ~ -2.03% → kabul
        $result2 = $g->okToOpen('BTCUSDT', $entry, 'LONG', 60, $slSafe);
        $this->assertTrue($result2['ok']);
        $this->assertNull($result2['reason']);
        $this->assertArrayHasKey('details', $result2);
    }

    public function test_ok_to_open_rejects_invalid_parameters(): void
    {
        $g = $this->guard();

        // Geçersiz entry price
        $result = $g->okToOpen('BTCUSDT', 0, 'LONG', 30, 62000.0);
        $this->assertFalse($result['ok']);
        $this->assertEquals('INVALID_ENTRY_PRICE', $result['reason']);
        $this->assertArrayHasKey('details', $result);

        // Geçersiz leverage
        $result2 = $g->okToOpen('BTCUSDT', 64000.0, 'LONG', 0, 62000.0);
        $this->assertFalse($result2['ok']);
        $this->assertEquals('INVALID_LEVERAGE', $result2['reason']);
        $this->assertArrayHasKey('details', $result2);
    }

    public function test_ok_to_open_returns_correct_details_for_successful_check(): void
    {
        $g = $this->guard();

        $entry = 64000.0;
        $sl = -12800.0; // 120% mesafe (entry: 64000, sl: -12800)
        $leverage = 1; // 1x kaldıraç → 1/1 = 100% likidasyon bandı

        $result = $g->okToOpen('BTCUSDT', $entry, 'LONG', $leverage, $sl);

        $this->assertTrue($result['ok']);
        $this->assertNull($result['reason']);
        $this->assertArrayHasKey('details', $result);
        $this->assertArrayHasKey('distance_pct', $result['details']);
        $this->assertArrayHasKey('min_required_pct', $result['details']);
        $this->assertArrayHasKey('leverage', $result['details']);
        $this->assertArrayHasKey('k_factor', $result['details']);

        // Detayları doğrula
        $this->assertEquals(1, $result['details']['leverage']);
        $this->assertEquals(1.2, $result['details']['k_factor']);
        $this->assertGreaterThan(0, $result['details']['distance_pct']);
        $this->assertGreaterThan(0, $result['details']['min_required_pct']);

        // Mesafe hesaplamasını doğrula
        $expectedDistance = abs(($entry - $sl) / $entry) * 100; // 120%
        $this->assertEquals(round($expectedDistance, 4), $result['details']['distance_pct']);
    }
}
