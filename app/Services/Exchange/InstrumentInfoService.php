<?php

declare(strict_types=1);

namespace App\Services\Exchange;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class InstrumentInfoService
{
    public function __construct(private array $cfg = [])
    {
        $this->cfg = $cfg ?: config('exchange.bybit');
    }

    /** @return array{symbol:string,tickSize:float,qtyStep:float,minOrderQty:float,maxLeverage:int} */
    public function get(string $symbol): array
    {
        return Cache::remember("bybit:instrument:$symbol", 300, function () use ($symbol) {
            $base = ($this->cfg['endpoints']['rest'][$this->cfg['testnet'] ? 'testnet' : 'mainnet'])
                ?? 'https://api-testnet.bybit.com';
            $resp = Http::baseUrl($base)
                ->get('/v5/market/instruments-info', [
                    'category' => $this->cfg['category'] ?? 'linear',
                    'symbol' => $symbol,
                ])->throw()->json();

            $item = $resp['result']['list'][0] ?? null;
            if (! $item) {
                throw new \RuntimeException('Instrument not found: '.$symbol);
            }
            $pf = $item['priceFilter'] ?? [];
            $lf = $item['lotSizeFilter'] ?? [];
            $lev = (int) (($item['leverageFilter']['maxLeverage'] ?? 75));

            return [
                'symbol' => $symbol,
                'tickSize' => (float) ($pf['tickSize'] ?? 0.1),
                'qtyStep' => (float) ($lf['qtyStep'] ?? 0.001),
                'minOrderQty' => (float) ($lf['minOrderQty'] ?? 0.001),
                'maxLeverage' => $lev,
            ];
        });
    }
}
