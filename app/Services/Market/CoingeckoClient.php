<?php

declare(strict_types=1);

namespace App\Services\Market;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Production-ready Coingecko client (no mocks).
 * - Safe defaults if config('services.coingecko') is missing.
 * - Retries, timeouts, caching, and tolerant JSON handling.
 */
final class CoingeckoClient
{
    private readonly string $base;

    private readonly int $timeout;

    private readonly int $ttl;

    public function __construct()
    {
        $cfg = (array) config('services.coingecko'); // null-safe → []
        $this->base = rtrim((string) ($cfg['base_url'] ?? 'https://api.coingecko.com/api/v3'), '/');
        $this->timeout = (int) ($cfg['timeout'] ?? 15);
        $ttl = (int) (config('trading.coingecko.cache_ttl') ?? 60);
        $this->ttl = max(10, $ttl); // guard against 0/negative
    }

    private function client(): PendingRequest
    {
        return Http::baseUrl($this->base)
            ->timeout($this->timeout)
            ->retry(2, 500, throw: false)
            ->acceptJson()
            ->withHeaders([
                'User-Agent' => 'SentinentXBot/1.0',
            ]);
    }

    /**
     * GET /global
     */
    public function global(): array
    {
        return Cache::remember('cg.global', $this->ttl, function (): array {
            $resp = $this->client()->get('/global')->throw()->json();

            return is_array($resp) ? $resp : [];
        });
    }

    /**
     * GET /coins/markets
     *
     * @param  string  $vs  vs_currency (e.g., 'usd')
     * @param  array  $ids  array of coin ids (e.g., ['bitcoin','ethereum'])
     * @param  string  $pcp  price_change_percentage (e.g., '1h,24h,7d')
     */
    public function coinsMarkets(string $vs = 'usd', array $ids = [], string $pcp = '1h,24h,7d'): array
    {
        $key = 'cg.cm.'.md5($vs.'|'.implode(',', $ids).'|'.$pcp);

        return Cache::remember($key, $this->ttl, function () use ($vs, $ids, $pcp): array {
            $query = array_filter([
                'vs_currency' => $vs,
                'ids' => $ids ? implode(',', $ids) : null,
                'price_change_percentage' => $pcp,
                'order' => 'market_cap_desc',
                'per_page' => 50,
                'page' => 1,
                'sparkline' => 'false',
            ], static fn ($v) => $v !== null);

            $resp = $this->client()->get('/coins/markets', $query)->throw()->json();

            return is_array($resp) ? $resp : [];
        });
    }

    /**
     * GET /simple/price
     *
     * @param  array  $ids  coin ids (e.g., ['bitcoin','ethereum'])
     * @param  string  $vs  vs_currencies (single currency like 'usd')
     */
    public function simplePrice(array $ids, string $vs = 'usd', bool $withChange = true): array
    {
        $key = 'cg.sp.'.md5($vs.'|'.implode(',', $ids).'|'.($withChange ? '1' : '0'));

        return Cache::remember($key, $this->ttl, function () use ($ids, $vs, $withChange): array {
            $query = [
                'ids' => implode(',', $ids),
                'vs_currencies' => $vs,
                'include_24hr_change' => $withChange ? 'true' : 'false',
            ];

            $resp = $this->client()->get('/simple/price', $query)->throw()->json();

            return is_array($resp) ? $resp : [];
        });
    }

    /**
     * GET /search/trending
     */
    public function trending(): array
    {
        return Cache::remember('cg.trending', $this->ttl, function (): array {
            $resp = $this->client()->get('/search/trending')->throw()->json();

            return is_array($resp) ? $resp : [];
        });
    }
}
