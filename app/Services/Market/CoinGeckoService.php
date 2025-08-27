<?php

declare(strict_types=1);

namespace App\Services\Market;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CoinGeckoService
{
    private const BASE_URL = 'https://api.coingecko.com/api/v3';

    private const CACHE_TTL = 300; // 5 dakika cache

    // CoinGecko ID mapping
    private const COIN_MAPPING = [
        'BTCUSDT' => 'bitcoin',
        'ETHUSDT' => 'ethereum',
        'SOLUSDT' => 'solana',
        'XRPUSDT' => 'ripple',
    ];

    /**
     * 4 ana coin için market verilerini toplu al
     *
     * @return array<string, array>
     */
    public function getMultiCoinData(): array
    {
        $cacheKey = 'coingecko_multicoin_data';

        return Cache::remember($cacheKey, self::CACHE_TTL, function () {
            try {
                $coinIds = implode(',', array_values(self::COIN_MAPPING));

                $headers = [];
                $apiKey = env('COINGECKO_API_KEY');
                if ($apiKey) {
                    $headers['x-cg-demo-api-key'] = $apiKey;
                }

                $response = Http::withHeaders($headers)
                    ->timeout(10)
                    ->retry(2, 1000)
                    ->get(self::BASE_URL.'/coins/markets', [
                        'vs_currency' => 'usd',
                        'ids' => $coinIds,
                        'order' => 'market_cap_desc',
                        'per_page' => 4,
                        'page' => 1,
                        'sparkline' => true,
                        'price_change_percentage' => '1h,24h,7d,30d',
                        'locale' => 'en',
                    ]);

                if (! $response->successful()) {
                    Log::error('CoinGecko API error', [
                        'status' => $response->status(),
                        'body' => $response->body(),
                    ]);

                    return $this->getFallbackData();
                }

                $data = $response->json();
                $result = [];

                foreach (self::COIN_MAPPING as $symbol => $coinId) {
                    $coinData = collect($data)->firstWhere('id', $coinId);

                    if ($coinData) {
                        $result[$symbol] = $this->formatCoinData($symbol, $coinData);
                    } else {
                        $result[$symbol] = $this->getEmptyData($symbol);
                    }
                }

                Log::info('CoinGecko data fetched successfully', [
                    'coins_count' => count($result),
                    'timestamp' => now()->toISOString(),
                ]);

                return $result;

            } catch (\Throwable $e) {
                Log::error('CoinGecko service error', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                return $this->getFallbackData();
            }
        });
    }

    /**
     * Tek coin için detaylı veri al
     *
     * @return array<string, mixed>
     */
    public function getCoinData(string $symbol): array
    {
        $symbol = strtoupper($symbol);

        if (! isset(self::COIN_MAPPING[$symbol])) {
            Log::warning('Unsupported coin symbol', ['symbol' => $symbol]);

            return $this->getEmptyData($symbol);
        }

        $cacheKey = "coingecko_coin_{$symbol}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($symbol) {
            try {
                $coinId = self::COIN_MAPPING[$symbol];

                $response = Http::timeout(10)
                    ->retry(2, 1000)
                    ->get(self::BASE_URL."/coins/{$coinId}", [
                        'localization' => false,
                        'tickers' => false,
                        'market_data' => true,
                        'community_data' => false,
                        'developer_data' => false,
                        'sparkline' => true,
                    ]);

                if (! $response->successful()) {
                    Log::error('CoinGecko single coin API error', [
                        'symbol' => $symbol,
                        'status' => $response->status(),
                    ]);

                    return $this->getEmptyData($symbol);
                }

                $data = $response->json();

                return $this->formatDetailedCoinData($symbol, $data);

            } catch (\Throwable $e) {
                Log::error('CoinGecko single coin error', [
                    'symbol' => $symbol,
                    'error' => $e->getMessage(),
                ]);

                return $this->getEmptyData($symbol);
            }
        });
    }

    /**
     * Market verilerini AI için formatla
     *
     * @return array<string, mixed>
     */
    private function formatCoinData(string $symbol, array $coinData): array
    {
        return [
            'symbol' => $symbol,
            'name' => $coinData['name'] ?? '',
            'current_price' => (float) ($coinData['current_price'] ?? 0.0),
            'market_cap' => (int) ($coinData['market_cap'] ?? 0),
            'market_cap_rank' => (int) ($coinData['market_cap_rank'] ?? 999),
            'volume_24h' => (float) ($coinData['total_volume'] ?? 0.0),
            'price_change_24h' => (float) ($coinData['price_change_24h'] ?? 0.0),
            'price_change_percentage_24h' => (float) ($coinData['price_change_percentage_24h'] ?? 0.0),
            'price_change_percentage_7d' => (float) ($coinData['price_change_percentage_7d_in_currency'] ?? 0.0),
            'price_change_percentage_30d' => (float) ($coinData['price_change_percentage_30d_in_currency'] ?? 0.0),
            'ath' => (float) ($coinData['ath'] ?? 0.0),
            'ath_change_percentage' => (float) ($coinData['ath_change_percentage'] ?? 0.0),
            'atl' => (float) ($coinData['atl'] ?? 0.0),
            'atl_change_percentage' => (float) ($coinData['atl_change_percentage'] ?? 0.0),
            'circulating_supply' => (float) ($coinData['circulating_supply'] ?? 0.0),
            'total_supply' => (float) ($coinData['total_supply'] ?? 0.0),
            'max_supply' => (float) ($coinData['max_supply'] ?? 0.0),
            'sparkline_7d' => $coinData['sparkline_in_7d']['price'] ?? [],
            'last_updated' => $coinData['last_updated'] ?? now()->toISOString(),
            'sentiment' => $this->calculateSentiment($coinData),
            'reliability_score' => $this->calculateReliabilityScore($coinData),
        ];
    }

    /**
     * Detaylı coin verilerini formatla
     *
     * @return array<string, mixed>
     */
    private function formatDetailedCoinData(string $symbol, array $data): array
    {
        $marketData = $data['market_data'] ?? [];

        return [
            'symbol' => $symbol,
            'name' => $data['name'] ?? '',
            'description' => substr($data['description']['en'] ?? '', 0, 500),
            'current_price' => (float) ($marketData['current_price']['usd'] ?? 0.0),
            'market_cap' => (int) ($marketData['market_cap']['usd'] ?? 0),
            'market_cap_rank' => (int) ($marketData['market_cap_rank'] ?? $data['market_cap_rank'] ?? 999),
            'volume_24h' => (float) ($marketData['total_volume']['usd'] ?? 0.0),
            'price_change_24h' => (float) ($marketData['price_change_24h'] ?? 0.0),
            'price_change_percentage_24h' => (float) ($marketData['price_change_percentage_24h'] ?? 0.0),
            'price_change_percentage_7d' => (float) ($marketData['price_change_percentage_7d'] ?? 0.0),
            'price_change_percentage_30d' => (float) ($marketData['price_change_percentage_30d'] ?? 0.0),
            'ath' => (float) ($marketData['ath']['usd'] ?? 0.0),
            'atl' => (float) ($marketData['atl']['usd'] ?? 0.0),
            'developer_score' => (float) ($data['developer_score'] ?? 0.0),
            'community_score' => (float) ($data['community_score'] ?? 0.0),
            'liquidity_score' => (float) ($data['liquidity_score'] ?? 0.0),
            'public_interest_score' => (float) ($data['public_interest_score'] ?? 0.0),
            'sentiment' => $this->calculateDetailedSentiment($data, $marketData),
            'reliability_score' => $this->calculateDetailedReliabilityScore($data, $marketData),
        ];
    }

    /**
     * Sentiment skorunu hesapla
     */
    private function calculateSentiment(array $coinData): float
    {
        $score = 50.0; // Nötr başlangıç

        // 24 saatlik değişim
        $change24h = (float) ($coinData['price_change_percentage_24h'] ?? 0.0);
        $score += $change24h * 2; // %1 değişim = 2 puan

        // 7 günlük trend
        $change7d = (float) ($coinData['price_change_percentage_7d_in_currency'] ?? 0.0);
        $score += $change7d * 0.5; // Haftalık trend daha az ağırlık

        // Volume momentum
        $volume = (float) ($coinData['total_volume'] ?? 0.0);
        $marketCap = (float) ($coinData['market_cap'] ?? 1.0);
        $volumeRatio = $marketCap > 0 ? ($volume / $marketCap) * 100 : 0;

        if ($volumeRatio > 10) {
            $score += 10;
        } // Yüksek volume pozitif
        elseif ($volumeRatio < 2) {
            $score -= 5;
        } // Düşük volume negatif

        return max(0.0, min(100.0, $score));
    }

    /**
     * Güvenilirlik skorunu hesapla
     */
    private function calculateReliabilityScore(array $coinData): float
    {
        $score = 0.0;

        // Market cap rank (düşük rank = yüksek güvenilirlik)
        $rank = (int) ($coinData['market_cap_rank'] ?? 999);
        if ($rank <= 10) {
            $score += 40;
        } elseif ($rank <= 50) {
            $score += 30;
        } elseif ($rank <= 100) {
            $score += 20;
        } else {
            $score += 10;
        }

        // Volume/Market cap oranı
        $volume = (float) ($coinData['total_volume'] ?? 0.0);
        $marketCap = (float) ($coinData['market_cap'] ?? 1.0);
        $volumeRatio = $marketCap > 0 ? ($volume / $marketCap) * 100 : 0;

        if ($volumeRatio >= 5 && $volumeRatio <= 20) {
            $score += 20;
        } // İdeal range
        elseif ($volumeRatio >= 2 && $volumeRatio <= 30) {
            $score += 15;
        } else {
            $score += 5;
        }

        // Volatilite (24h değişim)
        $volatility = abs((float) ($coinData['price_change_percentage_24h'] ?? 0.0));
        if ($volatility <= 5) {
            $score += 20;
        } // Düşük volatilite güvenilir
        elseif ($volatility <= 10) {
            $score += 15;
        } elseif ($volatility <= 20) {
            $score += 10;
        } else {
            $score += 5;
        }

        // ATH'den uzaklık (çok uzaksa risk)
        $currentPrice = (float) ($coinData['current_price'] ?? 0.0);
        $ath = (float) ($coinData['ath'] ?? 0.0);
        if ($ath > 0) {
            $athDistance = (($ath - $currentPrice) / $ath) * 100;
            if ($athDistance <= 20) {
                $score += 20;
            } // ATH'ye yakın
            elseif ($athDistance <= 50) {
                $score += 15;
            } else {
                $score += 10;
            }
        }

        return max(0.0, min(100.0, $score));
    }

    /**
     * Detaylı sentiment hesapla
     */
    private function calculateDetailedSentiment(array $data, array $marketData): float
    {
        $score = 50.0;

        // Community score etkisi
        $communityScore = (float) ($data['community_score'] ?? 0.0);
        $score += $communityScore * 0.3;

        // Developer score etkisi
        $developerScore = (float) ($data['developer_score'] ?? 0.0);
        $score += $developerScore * 0.2;

        // Market değişimler
        $change24h = (float) ($marketData['price_change_percentage_24h'] ?? 0.0);
        $score += $change24h * 2;

        return max(0.0, min(100.0, $score));
    }

    /**
     * Detaylı güvenilirlik hesapla
     */
    private function calculateDetailedReliabilityScore(array $data, array $marketData): float
    {
        $score = 0.0;

        // Likidite skoru
        $liquidityScore = (float) ($data['liquidity_score'] ?? 0.0);
        $score += $liquidityScore * 0.3;

        // Developer activity
        $developerScore = (float) ($data['developer_score'] ?? 0.0);
        $score += $developerScore * 0.2;

        // Community güçü
        $communityScore = (float) ($data['community_score'] ?? 0.0);
        $score += $communityScore * 0.2;

        // Market metrics
        $marketCap = (float) ($marketData['market_cap']['usd'] ?? 0.0);
        if ($marketCap > 10_000_000_000) {
            $score += 30;
        } // $10B+
        elseif ($marketCap > 1_000_000_000) {
            $score += 20;
        } // $1B+
        else {
            $score += 10;
        }

        return max(0.0, min(100.0, $score));
    }

    /**
     * Fallback veriler
     *
     * @return array<string, array>
     */
    private function getFallbackData(): array
    {
        $result = [];
        foreach (array_keys(self::COIN_MAPPING) as $symbol) {
            $result[$symbol] = $this->getEmptyData($symbol);
        }

        return $result;
    }

    /**
     * Boş veri yapısı
     *
     * @return array<string, mixed>
     */
    private function getEmptyData(string $symbol): array
    {
        return [
            'symbol' => $symbol,
            'name' => '',
            'current_price' => 0.0,
            'market_cap' => 0,
            'volume_24h' => 0.0,
            'price_change_24h' => 0.0,
            'price_change_percentage_24h' => 0.0,
            'sentiment' => 50.0,
            'reliability_score' => 25.0, // Düşük güvenilirlik
            'error' => 'Data not available',
        ];
    }
}
