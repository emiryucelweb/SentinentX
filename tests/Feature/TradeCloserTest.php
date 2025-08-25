<?php

namespace Tests\Feature;

use App\Services\Exchange\BybitClient;
use App\Services\Trading\TradeCloser;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class TradeCloserTest extends TestCase
{
    public function test_close_market_reduce_only(): void
    {
        Http::fake([
            'api-testnet.bybit.com/v5/order/create' => function ($request) {
                $b = $request->data();

                // reduceOnly true ve MARKET IOC olmalı
                return Http::response([
                    'retCode' => 0,
                    'retMsg' => 'OK',
                    'result' => ['orderId' => 'close-001', 'reduceOnly' => $b['reduceOnly'] ?? null, 'orderType' => $b['orderType'] ?? null],
                ], 200);
            },
        ]);

        $closer = new TradeCloser(new BybitClient([
            'testnet' => true, 'category' => 'linear', 'api_key' => 'K', 'api_secret' => 'S', 'recv_window' => 15000,
            'endpoints' => ['rest' => ['testnet' => 'https://api-testnet.bybit.com']],
        ]));

        $res = $closer->closeMarket('BTCUSDT', 'LONG', 0.01);
        $this->assertTrue($res['ok']);
        $this->assertSame(0, $res['result']['retCode']);
        Http::assertSent(function ($req) {
            $b = $req->data();

            return $b['orderType'] === 'MARKET' && $b['timeInForce'] === 'IOC' && $b['reduceOnly'] === true;
        });
    }
}
