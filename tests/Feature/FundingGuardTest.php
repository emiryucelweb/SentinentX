<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Services\Risk\FundingGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Fakes\FakeExchangeClient;
use Tests\TestCase;

final class FundingGuardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Zamanı sabitle - test'lerde flakiness'i önle
        Carbon::setTestNow(Carbon::create(2024, 1, 1, 12, 0, 0));

        // Bind interface to fake for testing
        $this->app->instance(
            \App\Contracts\Exchange\ExchangeClientInterface::class,
            new FakeExchangeClient
        );
    }

    protected function tearDown(): void
    {
        // Test zamanını temizle
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function fakeClient(array $tick): FakeExchangeClient
    {
        return new class($tick) extends FakeExchangeClient
        {
            public function __construct(private array $t) {}

            public function tickers(string $symbol, ?string $category = null): array
            {
                return $this->t;
            }
        };
    }

    public function test_blocks_inside_window_with_high_bps(): void
    {
        config(['trading.risk.funding_window_minutes' => 5]);
        config(['trading.risk.funding_limit_bps' => 30]);

        $now = Carbon::now()->timestamp * 1000;
        $tick = ['result' => ['list' => [[
            'fundingRate' => '0.0045', // 45 bps
            'nextFundingTime' => $now + 2 * 60 * 1000,
        ]]]];

        $fg = new FundingGuard($this->fakeClient($tick));
        $result = $fg->okToOpen('BTCUSDT', $now);

        $this->assertFalse($result['ok']);
        $this->assertEquals('FUNDING_WINDOW_BLOCK', $result['reason']);
        $this->assertArrayHasKey('details', $result);
        $this->assertArrayHasKey('funding_bps', $result['details']);
        $this->assertEquals(45.0, $result['details']['funding_bps']);
    }

    public function test_allows_outside_window_or_low_bps(): void
    {
        config(['trading.risk.funding_window_minutes' => 5]);
        config(['trading.risk.funding_limit_bps' => 30]);

        $now = Carbon::now()->timestamp * 1000;

        // Düşük bps
        $tick1 = ['result' => ['list' => [['fundingRate' => '0.0009', 'nextFundingTime' => $now + 2 * 60 * 1000]]]];
        $fg1 = new FundingGuard($this->fakeClient($tick1));
        $result1 = $fg1->okToOpen('BTCUSDT', $now);

        $this->assertTrue($result1['ok']);
        $this->assertNull($result1['reason']);
        $this->assertArrayHasKey('details', $result1);
        $this->assertEquals(9.0, $result1['details']['funding_bps']);

        // Uzak zaman
        $tick2 = ['result' => ['list' => [['fundingRate' => '0.0100', 'nextFundingTime' => $now + 30 * 60 * 1000]]]];
        $fg2 = new FundingGuard($this->fakeClient($tick2));
        $result2 = $fg2->okToOpen('BTCUSDT', $now);

        $this->assertTrue($result2['ok']);
        $this->assertNull($result2['reason']);
        $this->assertArrayHasKey('details', $result2);
        $this->assertEquals(100.0, $result2['details']['funding_bps']);
    }
}
