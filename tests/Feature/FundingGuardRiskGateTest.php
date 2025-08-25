<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Contracts\Exchange\ExchangeClientInterface;
use App\Services\Risk\CorrelationService;
use App\Services\Risk\FundingGuard;
use App\Services\Risk\RiskGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class FundingGuardRiskGateTest extends TestCase
{
    use RefreshDatabase;

    public function test_funding_rate_block_when_above_threshold(): void
    {
        // Exchange Client'i mock'la - yüksek funding rate
        $exchangeClient = $this->createMock(ExchangeClientInterface::class);
        $exchangeClient->method('tickers')
            ->willReturn([
                'result' => [
                    'list' => [
                        [
                            'fundingRate' => '0.006', // 0.6%
                            'nextFundingTime' => (string) ((time() + 180) * 1000), // 3dk sonra (timestamp * 1000)
                        ],
                    ],
                ],
            ]);

        // Gerçek Funding Guard'ı oluştur
        $fundingGuard = new FundingGuard($exchangeClient);

        // Gerçek Correlation Service'i oluştur
        $correlationService = new CorrelationService($exchangeClient);

        // Risk Guard'ı oluştur
        $riskGuard = new RiskGuard;

        // Test pozisyonu için veri
        $symbol = 'BTCUSDT';
        $price = 50000.0;
        $action = 'LONG';
        $leverage = 10;
        $stopLoss = 44000.0; // 12% mesafe (1/10 * 1.2 = 12% minimum)

        // Risk gate'i test et
        $result = $riskGuard->allowOpenWithGuards($symbol, $price, $action, $leverage, $stopLoss, $fundingGuard, $correlationService);

        // Assertions
        $this->assertFalse($result['ok']);
        $this->assertContains('FUNDING_WINDOW_BLOCK', $result['reasons']);
    }

    public function test_funding_rate_allow_when_below_threshold(): void
    {
        // Exchange Client'i mock'la - düşük funding rate
        $exchangeClient = $this->createMock(ExchangeClientInterface::class);
        $exchangeClient->method('tickers')
            ->willReturn([
                'result' => [
                    'list' => [
                        [
                            'fundingRate' => '0.002', // 0.2%
                            'nextFundingTime' => (string) (now()->addMinutes(10)->timestamp * 1000), // 10dk sonra
                        ],
                    ],
                ],
            ]);

        // Gerçek Funding Guard'ı oluştur
        $fundingGuard = new FundingGuard($exchangeClient);

        // Gerçek Correlation Service'i oluştur
        $correlationService = new CorrelationService($exchangeClient);

        // Risk Guard'ı oluştur
        $riskGuard = new RiskGuard;

        // Test pozisyonu için veri
        $symbol = 'BTCUSDT';
        $price = 50000.0;
        $action = 'LONG';
        $leverage = 10;
        $stopLoss = 44000.0; // 12% mesafe (1/10 * 1.2 = 12% minimum)

        // Risk gate'i test et
        $result = $riskGuard->allowOpenWithGuards($symbol, $price, $action, $leverage, $stopLoss, $fundingGuard, $correlationService);

        // Assertions
        $this->assertTrue($result['ok']);
    }
}
