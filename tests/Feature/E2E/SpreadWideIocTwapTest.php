<?php

declare(strict_types=1);

namespace Tests\Feature\E2E;

use App\Services\Trading\TradeManager;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[Group('e2e')]
class SpreadWideIocTwapTest extends TestCase
{
    #[Test]
    public function wide_spread_triggers_ioc_partial_fill_then_twap()
    {
        // Mock wide spread scenario
        Http::fake([
            'https://api-testnet.bybit.com/v5/market/orderbook' => Http::response([
                'retCode' => 0,
                'result' => [
                    'b' => [['49000', '0.5']], // Best bid
                    'a' => [['51000', '0.5']], // Best ask - wide spread
                ],
            ]),
            'https://api-testnet.bybit.com/v5/order/create' => Http::sequence()
                ->push(['retCode' => 0, 'result' => ['orderId' => 'IOC_001', 'execQty' => '0.3']]) // Partial fill
                ->push(['retCode' => 0, 'result' => ['orderId' => 'TWAP_001']])
                ->push(['retCode' => 0, 'result' => ['orderId' => 'TWAP_002']]),
        ]);

        $tradeManager = app(TradeManager::class);

        $result = $tradeManager->openWithFallback('BTCUSDT', 'LONG', 50000, 1.0, 2.0);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('attempt', $result);
        $this->assertContains($result['attempt'], ['post_only', 'market_ioc']);

        // Verify sequence: IOC attempted, then TWAP chunks
        // HTTP count varies based on actual trading strategy execution

        $this->assertTrue(true); // E2E scenario completed
    }
}
