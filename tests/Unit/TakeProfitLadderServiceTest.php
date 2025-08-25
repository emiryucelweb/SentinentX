<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\Trading\TakeProfitLadderService;
use Tests\Fakes\FakeExchangeClient;
use Tests\TestCase;

final class TakeProfitLadderServiceTest extends TestCase
{
    private TakeProfitLadderService $service;

    private FakeExchangeClient $exchange;

    protected function setUp(): void
    {
        parent::setUp();
        $this->exchange = new FakeExchangeClient;
        $this->service = new TakeProfitLadderService($this->exchange);
    }

    public function test_setup_tp_ladder_long_position(): void
    {
        $symbol = 'BTCUSDT';
        $side = 'LONG';
        $qty = 1.0;
        $entryPrice = 50000;
        $tpLevels = [
            ['price' => 52000, 'percentage' => 4.0],  // %4 profit
            ['price' => 55000, 'percentage' => 10.0], // %10 profit
            ['price' => 60000, 'percentage' => 20.0], // %20 profit
        ];

        $result = $this->service->setupTpLadder($symbol, $side, $qty, $entryPrice, $tpLevels);

        $this->assertTrue($result['ok']);
        $this->assertEquals(3, $result['total_levels']);
        $this->assertEquals(3, $result['successful_levels']);
        $this->assertEquals(1.0, $result['total_qty']);
        $this->assertEquals(50000, $result['entry_price']);
        $this->assertEquals('LONG', $result['side']);
        $this->assertCount(3, $result['tp_orders']);

        // TP seviyeleri yükselen sırada olmalı (LONG için)
        $this->assertEquals(52000, $result['details']['tp_levels'][0]['price']);
        $this->assertEquals(55000, $result['details']['tp_levels'][1]['price']);
        $this->assertEquals(60000, $result['details']['tp_levels'][2]['price']);

        // Her seviye için miktar yaklaşık eşit olmalı (floating point precision için)
        $this->assertEqualsWithDelta(0.33333333, $result['details']['level_quantities'][0], 0.00000001);
        $this->assertEqualsWithDelta(0.33333333, $result['details']['level_quantities'][1], 0.00000001);
        $this->assertEqualsWithDelta(0.33333334, $result['details']['level_quantities'][2], 0.00000001); // Kalan miktar
    }

    public function test_setup_tp_ladder_short_position(): void
    {
        $symbol = 'BTCUSDT';
        $side = 'SHORT';
        $qty = 1.0;
        $entryPrice = 50000;
        $tpLevels = [
            ['price' => 48000, 'percentage' => 4.0],  // %4 profit
            ['price' => 45000, 'percentage' => 10.0], // %10 profit
            ['price' => 40000, 'percentage' => 20.0], // %20 profit
        ];

        $result = $this->service->setupTpLadder($symbol, $side, $qty, $entryPrice, $tpLevels);

        $this->assertTrue($result['ok']);
        $this->assertEquals(3, $result['total_levels']);
        $this->assertEquals(3, $result['successful_levels']);

        // TP seviyeleri düşen sırada olmalı (SHORT için)
        $this->assertEquals(48000, $result['details']['tp_levels'][0]['price']);
        $this->assertEquals(45000, $result['details']['tp_levels'][1]['price']);
        $this->assertEquals(40000, $result['details']['tp_levels'][2]['price']);
    }

    public function test_tp_ladder_with_uneven_levels(): void
    {
        $symbol = 'ETHUSDT';
        $side = 'LONG';
        $qty = 2.0;
        $entryPrice = 3000;
        $tpLevels = [
            ['price' => 3150, 'percentage' => 5.0],   // %5 profit
            ['price' => 3300, 'percentage' => 10.0],  // %10 profit
        ];

        $result = $this->service->setupTpLadder($symbol, $side, $qty, $entryPrice, $tpLevels);

        $this->assertTrue($result['ok']);
        $this->assertEquals(2, $result['total_levels']);
        $this->assertEquals(2.0, $result['total_qty']);

        // Her seviye için miktar eşit dağıtılmalı
        $this->assertEquals(1.0, $result['details']['level_quantities'][0]);
        $this->assertEquals(1.0, $result['details']['level_quantities'][1]);
    }

    public function test_tp_ladder_with_single_level(): void
    {
        $symbol = 'ADAUSDT';
        $side = 'LONG';
        $qty = 1000;
        $entryPrice = 0.5;
        $tpLevels = [
            ['price' => 0.6, 'percentage' => 20.0], // %20 profit
        ];

        $result = $this->service->setupTpLadder($symbol, $side, $qty, $entryPrice, $tpLevels);

        $this->assertTrue($result['ok']);
        $this->assertEquals(1, $result['total_levels']);
        $this->assertEquals(1000, $result['total_qty']);
        $this->assertEquals(1000, $result['details']['level_quantities'][0]);
    }

    public function test_tp_ladder_level_ordering_long(): void
    {
        $side = 'LONG';
        $tpLevels = [
            ['price' => 60000, 'percentage' => 20.0],
            ['price' => 52000, 'percentage' => 4.0],
            ['price' => 55000, 'percentage' => 10.0],
        ];

        $result = $this->service->setupTpLadder('BTCUSDT', $side, 1.0, 50000, $tpLevels);

        // Seviyeler yükselen sırada olmalı
        $this->assertEquals(52000, $result['details']['tp_levels'][0]['price']);
        $this->assertEquals(55000, $result['details']['tp_levels'][1]['price']);
        $this->assertEquals(60000, $result['details']['tp_levels'][2]['price']);
    }

    public function test_tp_ladder_level_ordering_short(): void
    {
        $side = 'SHORT';
        $tpLevels = [
            ['price' => 40000, 'percentage' => 20.0],
            ['price' => 48000, 'percentage' => 4.0],
            ['price' => 45000, 'percentage' => 10.0],
        ];

        $result = $this->service->setupTpLadder('BTCUSDT', $side, 1.0, 50000, $tpLevels);

        // Seviyeler düşen sırada olmalı
        $this->assertEquals(48000, $result['details']['tp_levels'][0]['price']);
        $this->assertEquals(45000, $result['details']['tp_levels'][1]['price']);
        $this->assertEquals(40000, $result['details']['tp_levels'][2]['price']);
    }

    public function test_tp_ladder_quantity_distribution(): void
    {
        $qty = 1.0;
        $tpLevels = [
            ['price' => 52000, 'percentage' => 4.0],
            ['price' => 55000, 'percentage' => 10.0],
            ['price' => 60000, 'percentage' => 20.0],
        ];

        $result = $this->service->setupTpLadder('BTCUSDT', 'LONG', $qty, 50000, $tpLevels);

        $levelQtys = $result['details']['level_quantities'];

        // Toplam miktar korunmalı (floating point precision için)
        $totalCalculated = array_sum($levelQtys);
        $this->assertTrue(
            abs($totalCalculated - $qty) < 0.0000001,
            "Total quantity should be preserved. Expected: {$qty}, Got: {$totalCalculated}, Diff: ".abs($totalCalculated - $qty)
        );

        // Her seviye için miktar yaklaşık eşit olmalı
        $expectedQtyPerLevel = $qty / 3;
        $this->assertTrue(abs($levelQtys[0] - $expectedQtyPerLevel) < 0.00000001, 'First level quantity should be approximately equal');
        $this->assertTrue(abs($levelQtys[1] - $expectedQtyPerLevel) < 0.00000001, 'Second level quantity should be approximately equal');

        // Son seviyede kalan miktar olmalı (rounding errors için)
        $this->assertGreaterThan(0, $levelQtys[2], 'Last level should have remaining quantity');
    }

    public function test_tp_ladder_with_options(): void
    {
        $symbol = 'BTCUSDT';
        $side = 'LONG';
        $qty = 1.0;
        $entryPrice = 50000;
        $tpLevels = [
            ['price' => 52000, 'percentage' => 4.0],
        ];
        $opts = [
            'category' => 'linear',
            'orderLinkId' => 'test_tp_ladder_123',
        ];

        $result = $this->service->setupTpLadder($symbol, $side, $qty, $entryPrice, $tpLevels, $opts);

        $this->assertTrue($result['ok']);
        $this->assertCount(1, $result['tp_orders']);

        $tpOrder = $result['tp_orders'][0];
        $this->assertTrue($tpOrder['ok']);
        $this->assertEquals(52000, $tpOrder['price']);
        $this->assertEquals(4.0, $tpOrder['percentage']);
        $this->assertEquals(1.0, $tpOrder['qty']);
        $this->assertEquals('Sell', $tpOrder['side']); // LONG pozisyon için TP = Sell
    }

    public function test_tp_ladder_side_conversion(): void
    {
        $tpLevels = [['price' => 52000, 'percentage' => 4.0]];

        // LONG pozisyon için TP = Sell
        $resultLong = $this->service->setupTpLadder('BTCUSDT', 'LONG', 1.0, 50000, $tpLevels);
        $this->assertEquals('Sell', $resultLong['tp_orders'][0]['side']);

        // SHORT pozisyon için TP = Buy
        $resultShort = $this->service->setupTpLadder('BTCUSDT', 'SHORT', 1.0, 50000, $tpLevels);
        $this->assertEquals('Buy', $resultShort['tp_orders'][0]['side']);
    }

    public function test_check_tp_ladder_status(): void
    {
        $tpOrders = [
            ['ok' => true, 'order_id' => 'order1'],
            ['ok' => true, 'order_id' => 'order2'],
            ['ok' => true, 'order_id' => 'order3'],
        ];

        $status = $this->service->checkTpLadderStatus($tpOrders);

        $this->assertEquals(3, $status['total_orders']);
        $this->assertEquals(0, $status['filled_orders']);
        $this->assertEquals(3, $status['pending_orders']);
        $this->assertEquals(0, $status['cancelled_orders']);
        $this->assertEquals(0.0, $status['completion_rate']);
    }

    public function test_check_tp_ladder_status_with_failed_orders(): void
    {
        $tpOrders = [
            ['ok' => true, 'order_id' => 'order1'],
            ['ok' => false, 'order_id' => null, 'error' => 'Failed'],
            ['ok' => true, 'order_id' => 'order3'],
        ];

        $status = $this->service->checkTpLadderStatus($tpOrders);

        $this->assertEquals(3, $status['total_orders']);
        $this->assertEquals(0, $status['filled_orders']);
        $this->assertEquals(2, $status['pending_orders']); // Sadece başarılı order'lar
        $this->assertEquals(0, $status['cancelled_orders']);
    }

    public function test_close_tp_ladder(): void
    {
        $tpOrders = [
            ['ok' => true, 'order_id' => 'order1'],
            ['ok' => true, 'order_id' => 'order2'],
            ['ok' => false, 'order_id' => null], // Failed order
        ];

        $result = $this->service->closeTpLadder($tpOrders);

        $this->assertTrue($result['ok']);
        $this->assertEquals(2, $result['cancelled_orders']);
        $this->assertEquals(3, $result['total_orders']);
        $this->assertEmpty($result['errors']);
    }

    public function test_edge_case_zero_quantity(): void
    {
        $tpLevels = [['price' => 52000, 'percentage' => 4.0]];

        $result = $this->service->setupTpLadder('BTCUSDT', 'LONG', 0.0, 50000, $tpLevels);

        $this->assertTrue($result['ok']);
        $this->assertEquals(0.0, $result['total_qty']);
        $this->assertEquals(0.0, $result['details']['level_quantities'][0]);
    }

    public function test_edge_case_single_decimal_quantity(): void
    {
        $tpLevels = [
            ['price' => 52000, 'percentage' => 4.0],
            ['price' => 55000, 'percentage' => 10.0],
        ];

        $result = $this->service->setupTpLadder('BTCUSDT', 'LONG', 0.001, 50000, $tpLevels);

        $this->assertTrue($result['ok']);
        $this->assertEquals(0.001, $result['total_qty']);

        $levelQtys = $result['details']['level_quantities'];
        $this->assertEquals(0.0005, $levelQtys[0]);
        $this->assertEquals(0.0005, $levelQtys[1]);
    }

    public function test_edge_case_very_small_quantity(): void
    {
        $tpLevels = [['price' => 52000, 'percentage' => 4.0]];

        $result = $this->service->setupTpLadder('BTCUSDT', 'LONG', 0.00000001, 50000, $tpLevels);

        $this->assertTrue($result['ok']);
        $this->assertEquals(0.00000001, $result['total_qty']);
        $this->assertEquals(0.00000001, $result['details']['level_quantities'][0]);
    }

    public function test_check_tp_ladder_status_basic_functionality(): void
    {
        // Test basic functionality without mocking exchange calls
        $tpOrders = [
            ['ok' => true, 'order_id' => 'order1'],
            ['ok' => true, 'order_id' => 'order2'],
            ['ok' => false, 'order_id' => null], // Failed order
        ];

        $result = $this->service->checkTpLadderStatus($tpOrders);

        $this->assertIsArray($result);
        $this->assertEquals(3, $result['total_orders']);
        $this->assertArrayHasKey('filled_orders', $result);
        $this->assertArrayHasKey('pending_orders', $result);
        $this->assertArrayHasKey('cancelled_orders', $result);
        $this->assertArrayHasKey('completion_rate', $result);
    }

    public function test_tp_ladder_with_extreme_levels(): void
    {
        $tpLevels = [
            ['price' => 100000, 'percentage' => 100.0], // 100% gain
            ['price' => 45000, 'percentage' => -10.0],  // Loss scenario
        ];

        $result = $this->service->setupTpLadder('BTCUSDT', 'LONG', 0.001, 50000, $tpLevels);

        $this->assertIsArray($result);
        // Should handle extreme values gracefully
        $this->assertArrayHasKey('ok', $result);
    }
}
