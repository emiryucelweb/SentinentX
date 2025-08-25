<?php

namespace App\Services\Risk;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class StablecoinHealthCheck
{
    private const USDT_THRESHOLD_LOW = 0.98;

    private const USDT_THRESHOLD_HIGH = 1.02;

    private const USDC_THRESHOLD_LOW = 0.98;

    private const USDC_THRESHOLD_HIGH = 1.02;

    public function checkUsdtHealth(): array
    {
        try {
            $response = Http::get('https://api-testnet.bybit.com/v5/market/tickers', [
                'category' => 'spot',
                'symbol' => 'USDTUSD',
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $price = (float) ($data['result']['list'][0]['lastPrice'] ?? 1.0);

                return [
                    'healthy' => $this->isUsdtHealthy($price),
                    'price' => $price,
                    'status' => $this->getUsdtStatus($price),
                    'timestamp' => now()->toISOString(),
                ];
            }

            return $this->getDefaultHealthStatus('USDT', 'API_ERROR');
        } catch (\Exception $e) {
            Log::warning('USDT health check failed', ['error' => $e->getMessage()]);

            return $this->getDefaultHealthStatus('USDT', 'EXCEPTION');
        }
    }

    public function checkUsdcHealth(): array
    {
        try {
            $response = Http::get('https://api-testnet.bybit.com/v5/market/tickers', [
                'category' => 'spot',
                'symbol' => 'USDCUSD',
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $price = (float) ($data['result']['list'][0]['lastPrice'] ?? 1.0);

                return [
                    'healthy' => $this->isUsdcHealthy($price),
                    'price' => $price,
                    'status' => $this->getUsdcStatus($price),
                    'timestamp' => now()->toISOString(),
                ];
            }

            return $this->getDefaultHealthStatus('USDC', 'API_ERROR');
        } catch (\Exception $e) {
            Log::warning('USDC health check failed', ['error' => $e->getMessage()]);

            return $this->getDefaultHealthStatus('USDC', 'EXCEPTION');
        }
    }

    public function checkAllStablecoins(): array
    {
        $usdt = $this->checkUsdtHealth();
        $usdc = $this->checkUsdcHealth();

        return [
            'overall_healthy' => $usdt['healthy'] && $usdc['healthy'],
            'usdt' => $usdt,
            'usdc' => $usdc,
            'checked_at' => now()->toISOString(),
        ];
    }

    private function isUsdtHealthy(float $price): bool
    {
        return $price >= self::USDT_THRESHOLD_LOW && $price <= self::USDT_THRESHOLD_HIGH;
    }

    private function isUsdcHealthy(float $price): bool
    {
        return $price >= self::USDC_THRESHOLD_LOW && $price <= self::USDC_THRESHOLD_HIGH;
    }

    private function getUsdtStatus(float $price): string
    {
        if ($price < self::USDT_THRESHOLD_LOW) {
            return 'DEPEG_LOW';
        }

        if ($price > self::USDT_THRESHOLD_HIGH) {
            return 'DEPEG_HIGH';
        }

        return 'HEALTHY';
    }

    private function getUsdcStatus(float $price): string
    {
        if ($price < self::USDC_THRESHOLD_LOW) {
            return 'DEPEG_LOW';
        }

        if ($price > self::USDC_THRESHOLD_HIGH) {
            return 'DEPEG_HIGH';
        }

        return 'HEALTHY';
    }

    private function getDefaultHealthStatus(string $coin, string $errorType): array
    {
        return [
            'healthy' => false,
            'price' => 1.0,
            'status' => $errorType,
            'coin' => $coin,
            'timestamp' => now()->toISOString(),
        ];
    }
}
