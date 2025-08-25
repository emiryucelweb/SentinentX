<?php

declare(strict_types=1);

namespace App\Services\Risk;

use App\Contracts\Exchange\ExchangeClientInterface;
use Illuminate\Support\Arr;

/**
 * Funding Guard: Funding penceresi yaklaşırken aşırı funding oranlarında yeni pozisyon açmayı engeller.
 * Kural: now → nextFundingTime aralığı (dakika) <= window && |fundingRate| (bps) > limit_bps ⇒ BLOK.
 * Varsayılanlar: window=5dk, limit_bps=30 (0.30%).
 */
class FundingGuard
{
    public function __construct(private readonly ExchangeClientInterface $ex) {}

    /**
     * @return array{
     *   ok: bool,
     *   reason: string|null,
     *   details: array<string,mixed>
     * }
     */
    public function okToOpen(string $symbol, ?int $nowMs = null, ?string $category = 'linear'): array
    {
        $windowMin = (int) config('trading.risk.funding_window_minutes', 5);
        $limitBps = (int) config('trading.risk.funding_limit_bps', 30);
        if ($windowMin <= 0 || $limitBps <= 0) {
            return [
                'ok' => true,
                'reason' => null,
                'details' => ['message' => 'Funding guard disabled'],
            ];
        }

        $tick = $this->ex->tickers($symbol, $category);
        $rate = Arr::get($tick, 'result.list.0.fundingRate');
        $next = (int) (Arr::get($tick, 'result.list.0.nextFundingTime', 0)); // ms epoch

        if ($rate === null || $next <= 0) {
            return [
                'ok' => true,
                'reason' => null,
                'details' => ['message' => 'No funding data available'],
            ];
        }

        $now = $nowMs ?? (int) (microtime(true) * 1000);
        $minsLeft = abs($next - $now) / 60000.0;
        $absBps = abs((float) $rate) * 10000.0;

        if ($minsLeft <= $windowMin && $absBps > $limitBps) {
            return [
                'ok' => false,
                'reason' => 'FUNDING_WINDOW_BLOCK',
                'details' => [
                    'window_minutes' => $windowMin,
                    'limit_bps' => $limitBps,
                    'mins_left' => round($minsLeft, 2),
                    'funding_bps' => round($absBps, 2),
                    'funding_rate' => (float) $rate,
                    'next_funding_time' => $next,
                    'current_time' => $now,
                ],
            ];
        }

        return [
            'ok' => true,
            'reason' => null,
            'details' => [
                'window_minutes' => $windowMin,
                'limit_bps' => $limitBps,
                'mins_left' => round($minsLeft, 2),
                'funding_bps' => round($absBps, 2),
                'funding_rate' => (float) $rate,
                'next_funding_time' => $next,
                'current_time' => $now,
            ],
        ];
    }

    /**
     * Funding zamanlaması optimizasyonu
     *
     * @param  string  $symbol  Sembol
     * @param  string  $category  Kategori
     * @return array ['optimal_entry' => bool, 'time_to_funding' => int, 'recommendation' => string]
     */
    public function getOptimalEntryTiming(string $symbol, string $category = 'linear'): array
    {
        $tick = $this->ex->tickers($symbol, $category);
        $nextFunding = (int) (Arr::get($tick, 'result.list.0.nextFundingTime', 0));
        $currentTime = (int) (microtime(true) * 1000);

        if ($nextFunding <= 0) {
            return [
                'optimal_entry' => true,
                'time_to_funding' => 0,
                'recommendation' => 'No funding data available',
            ];
        }

        $timeToFunding = ($nextFunding - $currentTime) / 60000; // Dakika cinsinden

        // Funding'den 30 dakika önce veya 15 dakika sonra optimal
        $optimalEntry = $timeToFunding > 30 || $timeToFunding < -15;

        $recommendation = match (true) {
            $timeToFunding > 30 => 'Optimal entry time - far from funding',
            $timeToFunding > 15 => 'Good entry time - approaching funding',
            $timeToFunding > 0 => 'Avoid entry - funding window approaching',
            $timeToFunding > -15 => 'Wait for funding to settle',
            default => 'Optimal entry time - funding settled',
        };

        return [
            'optimal_entry' => $optimalEntry,
            'time_to_funding' => (int) $timeToFunding,
            'recommendation' => $recommendation,
            'next_funding_time' => $nextFunding,
            'current_time' => $currentTime,
        ];
    }

    /**
     * Funding bazlı pozisyon boyutlandırma
     *
     * @param  float  $baseQty  Temel pozisyon boyutu
     * @param  string  $symbol  Sembol
     * @param  string  $category  Kategori
     * @return array ['qty' => float, 'funding_adjusted' => bool, 'factor' => float]
     */
    public function calculateFundingAdjustedPosition(
        float $baseQty,
        string $symbol,
        string $category = 'linear'
    ): array {
        $timing = $this->getOptimalEntryTiming($symbol, $category);
        $tick = $this->ex->tickers($symbol, $category);
        $fundingRate = (float) (Arr::get($tick, 'result.list.0.fundingRate', 0));

        $factor = 1.0;
        $adjusted = false;

        // Funding yaklaşıyorsa pozisyon boyutunu azalt
        if ($timing['time_to_funding'] > 0 && $timing['time_to_funding'] <= 30) {
            $factor = 0.7; // %30 azalt
            $adjusted = true;
        }

        // Yüksek funding oranında pozisyon boyutunu azalt
        if (abs($fundingRate) > 0.001) { // %0.1'den fazla
            $factor *= 0.8; // %20 daha azalt
            $adjusted = true;
        }

        return [
            'qty' => $baseQty * $factor,
            'funding_adjusted' => $adjusted,
            'factor' => $factor,
            'original_qty' => $baseQty,
            'funding_rate' => $fundingRate,
            'timing' => $timing,
        ];
    }

    /**
     * Funding penceresi analizi
     *
     * @param  array  $symbols  Sembol listesi
     * @param  string  $category  Kategori
     * @return array ['windows' => array, 'recommendations' => array]
     */
    public function analyzeFundingWindows(array $symbols, string $category = 'linear'): array
    {
        $windows = [];
        $recommendations = [];

        foreach ($symbols as $symbol) {
            $timing = $this->getOptimalEntryTiming($symbol, $category);
            $tick = $this->ex->tickers($symbol, $category);
            $fundingRate = (float) (Arr::get($tick, 'result.list.0.fundingRate', 0));

            $windows[$symbol] = [
                'time_to_funding' => $timing['time_to_funding'],
                'optimal_entry' => $timing['optimal_entry'],
                'funding_rate' => $fundingRate,
                'recommendation' => $timing['recommendation'],
            ];

            // Genel öneriler
            if ($timing['time_to_funding'] <= 15 && $timing['time_to_funding'] > 0) {
                $recommendations[] = "Avoid opening $symbol - funding in {$timing['time_to_funding']} minutes";
            }

            if (abs($fundingRate) > 0.002) { // %0.2'den fazla
                $recommendations[] = "High funding rate for $symbol: ".round($fundingRate * 100, 3).'%';
            }
        }

        return [
            'windows' => $windows,
            'recommendations' => $recommendations,
        ];
    }
}
