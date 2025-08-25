<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Contracts\Exchange\ExchangeClientInterface;
use App\Services\Trading\OcoService;
use Tests\TestCase;

final class OcoServiceTest extends TestCase
{
    private OcoService $ocoService;

    private ExchangeClientInterface $mockExchange;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock exchange client interface
        $this->mockExchange = $this->createMock(ExchangeClientInterface::class);
        $this->ocoService = new OcoService($this->mockExchange, 3, 50); // 50ms delay (eski: 1000ms)
    }

    public function test_setup_oco_success(): void
    {
        $this->mockExchange->method('createOcoOrder')
            ->willReturn([
                'ok' => true,
                'result' => [
                    'ocoId' => 'oco-123',
                    'orderStatus' => 'Created',
                ],
            ]);

        $result = $this->ocoService->setupOco(
            'BTCUSDT',
            'LONG',
            1.0,
            51000.0,
            49000.0
        );

        $this->assertTrue($result['ok']);
        $this->assertEquals('oco-123', $result['oco_id']);
        $this->assertEquals(1, $result['attempt']);
        $this->assertArrayHasKey('orderStatus', $result['details']);
    }

    public function test_setup_oco_failure(): void
    {
        $this->mockExchange->method('createOcoOrder')
            ->willReturn([
                'ok' => false,
                'error_message' => 'Insufficient balance',
            ]);

        $result = $this->ocoService->setupOco(
            'BTCUSDT',
            'LONG',
            1.0,
            51000.0,
            49000.0
        );

        $this->assertFalse($result['ok']);
        $this->assertNull($result['oco_id']);
        $this->assertEquals('Insufficient balance', $result['error']);
        $this->assertEquals(1, $result['attempt']);
    }

    public function test_setup_oco_with_retry_success_on_first_attempt(): void
    {
        $this->mockExchange->method('createOcoOrder')
            ->willReturn([
                'ok' => true,
                'result' => [
                    'ocoId' => 'oco-123',
                    'orderStatus' => 'Created',
                ],
            ]);

        $result = $this->ocoService->setupOcoWithRetry(
            'BTCUSDT',
            'LONG',
            1.0,
            51000.0,
            49000.0
        );

        $this->assertTrue($result['ok']);
        $this->assertEquals('oco-123', $result['oco_id']);
        $this->assertEquals(1, $result['attempts']);
    }

    public function test_setup_oco_with_retry_success_on_second_attempt(): void
    {
        $this->mockExchange->method('createOcoOrder')
            ->willReturnOnConsecutiveCalls(
                [
                    'ok' => false,
                    'error_message' => 'Temporary error',
                ],
                [
                    'ok' => true,
                    'result' => [
                        'ocoId' => 'oco-123',
                        'orderStatus' => 'Created',
                    ],
                ]
            );

        $result = $this->ocoService->setupOcoWithRetry(
            'BTCUSDT',
            'LONG',
            1.0,
            51000.0,
            49000.0
        );

        $this->assertTrue($result['ok']);
        $this->assertEquals('oco-123', $result['oco_id']);
        $this->assertEquals(2, $result['attempts']);
    }

    public function test_setup_oco_with_retry_failure_after_all_attempts(): void
    {
        $this->mockExchange->method('createOcoOrder')
            ->willReturn([
                'ok' => false,
                'error_message' => 'Permanent error',
            ]);

        $result = $this->ocoService->setupOcoWithRetry(
            'BTCUSDT',
            'LONG',
            1.0,
            51000.0,
            49000.0
        );

        $this->assertFalse($result['ok']);
        $this->assertNull($result['oco_id']);
        $this->assertEquals(3, $result['attempts']);
        $this->assertEquals('Permanent error', $result['error']);
    }

    public function test_cancel_oco_success(): void
    {
        $this->mockExchange->method('cancelOcoOrder')
            ->willReturn([
                'ok' => true,
                'result' => [
                    'orderId' => 'oco-123',
                    'status' => 'Cancelled',
                ],
            ]);

        $result = $this->ocoService->cancelOco('BTCUSDT', 'oco-123');

        $this->assertTrue($result['ok']);
        $this->assertArrayHasKey('orderId', $result['details']);
    }

    public function test_cancel_oco_failure(): void
    {
        $this->mockExchange->method('cancelOcoOrder')
            ->willReturn([
                'ok' => false,
                'error_message' => 'Order not found',
            ]);

        $result = $this->ocoService->cancelOco('BTCUSDT', 'oco-123');

        $this->assertFalse($result['ok']);
    }

    public function test_check_oco_status_success(): void
    {
        $this->mockExchange->method('getOcoOrder')
            ->willReturn([
                'ok' => true,
                'result' => [
                    'orderStatus' => 'Active',
                    'ocoId' => 'oco-123',
                ],
            ]);

        $result = $this->ocoService->checkOcoStatus('BTCUSDT', 'oco-123');

        $this->assertTrue($result['ok']);
        $this->assertEquals('Active', $result['status']);
        $this->assertArrayHasKey('ocoId', $result['details']);
    }

    public function test_check_oco_status_failure(): void
    {
        $this->mockExchange->method('getOcoOrder')
            ->willReturn([
                'ok' => false,
                'error_message' => 'Order not found',
            ]);

        $result = $this->ocoService->checkOcoStatus('BTCUSDT', 'oco-123');

        $this->assertFalse($result['ok']);
        $this->assertEquals('UNKNOWN', $result['status']);
    }

    public function test_relink_oco(): void
    {
        $this->mockExchange->method('createOcoOrder')
            ->willReturn([
                'ok' => true,
                'result' => [
                    'ocoId' => 'oco-relink-456',
                    'orderStatus' => 'Created',
                ],
            ]);

        $result = $this->ocoService->relinkOco(
            'BTCUSDT',
            'LONG',
            0.5,
            50500.0,
            49500.0
        );

        $this->assertTrue($result['ok']);
        $this->assertEquals('oco-relink-456', $result['oco_id']);
        $this->assertEquals(1, $result['attempts']);
    }

    public function test_oco_parameters_correctness(): void
    {
        $this->mockExchange->method('createOcoOrder')
            ->willReturn([
                'ok' => true,
                'result' => ['ocoId' => 'oco-123'],
            ]);

        $result = $this->ocoService->setupOco(
            'BTCUSDT',
            'SHORT',
            2.0,
            52000.0,
            48000.0,
            ['category' => 'linear']
        );

        $this->assertTrue($result['ok']);

        // Exchange'e gönderilen parametreleri kontrol et
        $this->mockExchange->expects($this->once())
            ->method('createOcoOrder')
            ->with($this->callback(function ($params) {
                return $params['symbol'] === 'BTCUSDT' &&
                       $params['side'] === 'Sell' &&
                       $params['qty'] === 2.0 &&
                       $params['takeProfit'] === 52000.0 &&
                       $params['stopLoss'] === 48000.0 &&
                       $params['tpslMode'] === 'Partial' &&
                       $params['tpOrderType'] === 'Limit' &&
                       $params['slOrderType'] === 'Market' &&
                       $params['tpTriggerBy'] === 'MarkPrice' &&
                       $params['slTriggerBy'] === 'MarkPrice' &&
                       $params['reduceOnly'] === true &&
                       $params['timeInForce'] === 'GTC' &&
                       $params['category'] === 'linear';
            }));

        $this->ocoService->setupOco(
            'BTCUSDT',
            'SHORT',
            2.0,
            52000.0,
            48000.0,
            ['category' => 'linear']
        );
    }
}
