<?php

declare(strict_types=1);

namespace App\Services\Exchange;

use Illuminate\Support\Facades\Http;

class AccountService
{
    public function __construct(private array $cfg = [])
    {
        $this->cfg = $cfg ?: config('exchange.bybit');
    }

    public function equity(): float
    {
        $base = ($this->cfg['endpoints']['rest'][$this->cfg['testnet'] ? 'testnet' : 'mainnet'])
            ?? 'https://api-testnet.bybit.com';
        $auth = $this->authHeaders('');
        $resp = Http::baseUrl($base)
            ->withHeaders($auth)
            ->get('/v5/account/wallet-balance', [
                'accountType' => $this->cfg['account_type'] ?? 'UNIFIED',
                'coin' => 'USDT',
            ])->throw()->json();
        $e = (float) ($resp['result']['list'][0]['totalEquity'] ?? 0);

        return $e;
    }

    /** Basit marjin kullanım metriği */
    public function marginUtilization(float $notional, float $equity, int $leverage): float
    {
        if ($equity <= 0 || $leverage <= 0) {
            return 0.0;
        }
        $cap = $equity * $leverage;

        return max(0.0, min(1.0, $notional / $cap));
    }

    private function authHeaders(string $body): array
    {
        $ts = (string) (int) (microtime(true) * 1000);
        $recv = (string) ($this->cfg['recv_window'] ?? 15000);
        $apiKey = $this->cfg['api_key'] ?? '';
        $secret = $this->cfg['api_secret'] ?? '';
        $signStr = $ts.$apiKey.$recv.$body;
        $sig = hash_hmac('sha256', $signStr, $secret);

        return [
            'X-BAPI-API-KEY' => $apiKey,
            'X-BAPI-SIGN' => $sig,
            'X-BAPI-SIGN-TYPE' => '2',
            'X-BAPI-TIMESTAMP' => $ts,
            'X-BAPI-RECV-WINDOW' => $recv,
        ];
    }
}
