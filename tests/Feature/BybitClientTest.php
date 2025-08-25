<?php

namespace Tests\Feature;

use App\Services\Exchange\BybitClient;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class BybitClientTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Override parent HTTP setup completely for exchange tests
        Http::fake(); // Reset all fakes
    }

    public function test_create_order_signs_and_sends(): void
    {
        Http::fake([
            'https://api-testnet.bybit.com/v5/order/create' => function ($request) {
                // Headerlar mevcut mu?
                $this->assertTrue($request->hasHeader('X-BAPI-API-KEY'));
                $this->assertTrue($request->hasHeader('X-BAPI-SIGN'));
                $this->assertTrue($request->hasHeader('X-BAPI-TIMESTAMP'));
                $body = $request->data();
                $this->assertSame('linear', $body['category']);
                $this->assertSame('BTCUSDT', $body['symbol']);

                return Http::response([
                    'retCode' => 0,
                    'retMsg' => 'OK',
                    'result' => ['orderId' => 'abc'],
                ], 200);
            },
        ]);

        $cli = new BybitClient([
            'testnet' => true,
            'category' => 'linear',
            'api_key' => 'K',
            'api_secret' => 'S',
            'recv_window' => 15000,
            'endpoints' => ['rest' => ['testnet' => 'https://api-testnet.bybit.com']],
        ]);
        $res = $cli->createOrder('BTCUSDT', 'LONG', 'LIMIT', 0.01, 30000.0);
        $this->assertTrue($res['ok']);

        // Test success response structure
        $this->assertArrayHasKey('result', $res);
        $this->assertArrayHasKey('retCode', $res['result']);

        $this->assertSame(0, $res['result']['retCode']);
        $this->assertSame('OK', $res['result']['retMsg']);
        $this->assertArrayHasKey('orderId', $res['result']['result']);
        $this->assertArrayHasKey('orderId', $res);

        // Verify orderId consistency (using default mock value)
        $this->assertSame($res['result']['result']['orderId'], $res['orderId']);
    }
}
