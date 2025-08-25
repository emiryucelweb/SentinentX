<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Services\Risk\CorrelationService;
use Tests\Fakes\FakeExchangeClient;
use Tests\TestCase;

final class CorrelationServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Bind interface to fake for testing
        $this->app->instance(
            \App\Contracts\Exchange\ExchangeClientInterface::class,
            new FakeExchangeClient
        );
    }

    private function fakeKline(array $series): FakeExchangeClient
    {
        return new class($series) extends FakeExchangeClient
        {
            public function __construct(private array $s) {}

            public function kline(string $symbol, string $interval = '5', int $limit = 50, ?string $category = null): array
            {
                return ['result' => ['list' => $this->s[$symbol]]];
            }
        };
    }

    public function test_matrix_and_threshold(): void
    {
        // Sentetik kapanışlar (ts, o,h,l,c,vol)
        $base = [];
        $t0 = 1_700_000_000_000;
        $cl = 100.0;
        for ($i = 0; $i < 61; $i++) {
            $cl *= 1.001;
            $base[] = [$t0 + $i * 300000, 0, 0, 0, $cl, 0];
        }

        // ETH: BTC ile yüksek korelasyon için benzer pattern ama farklı başlangıç
        $eth = [];
        $cl2 = 50.0;
        for ($i = 0; $i < 61; $i++) {
            $cl2 *= 1.001; // Aynı çarpan kullan
            $eth[] = [$t0 + $i * 300000, 0, 0, 0, $cl2, 0];
        }

        // XRP: Farklı pattern (zigzag)
        $xrp = [];
        $cl3 = 1.0;
        for ($i = 0; $i < 61; $i++) {
            $cl3 *= (1.0 + ((($i % 2) ? 1 : -1) * 0.005));
            $xrp[] = [$t0 + $i * 300000, 0, 0, 0, $cl3, 0];
        }

        $client = $this->fakeKline(['BTCUSDT' => $base, 'ETHUSDT' => $eth, 'XRPUSDT' => $xrp]);
        $svc = new CorrelationService($client);

        $m = $svc->matrix(['BTCUSDT', 'ETHUSDT', 'XRPUSDT']);
        $this->assertGreaterThan(0.85, $m['BTCUSDT']['ETHUSDT']);
        $this->assertLessThan(0.85, abs($m['BTCUSDT']['XRPUSDT']));

        $this->assertTrue($svc->isHighlyCorrelated(['BTCUSDT', 'XRPUSDT'], 'ETHUSDT', 0.85));
        $this->assertFalse($svc->isHighlyCorrelated(['ETHUSDT'], 'XRPUSDT', 0.85));
    }
}
