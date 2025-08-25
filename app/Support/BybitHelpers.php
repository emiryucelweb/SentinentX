<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\Http;

final class BybitHelpers
{
    /**
     * Bybit sunucu zamanı (ms). Ulaşamazsa yerel zamanı ms döndürür.
     */
    public static function serverTime(?string $base = null): int
    {
        $url = rtrim($base ?: 'https://api-testnet.bybit.com', '/').'/v5/market/time';
        try {
            $res = Http::timeout(10)->acceptJson()->get($url);
            $json = $res->json();
            if ($res->ok() && is_array($json) && isset($json['time'])) {
                return (int) $json['time'];
            }
        } catch (\Throwable $e) {
            // no-op
        }

        return (int) round(microtime(true) * 1000);
        // Not: Bybit bazı örneklerde saniye döndürebiliyor; burada ms bekleniyor.
    }

    /**
     * v5 imza: timestamp + api_key + recv_window + (query/body)
     */
    public static function signV5(
        string $ts,
        string $apiKey,
        string $recvWindow,
        string $secret,
        string $queryOrBody = ''
    ): string {
        $preSign = $ts.$apiKey.$recvWindow.$queryOrBody;

        return hash_hmac('sha256', $preSign, $secret);
    }

    public static function headers(string $apiKey, string $sign, string $ts, string $recvWindow): array
    {
        return [
            'X-BAPI-API-KEY' => $apiKey,
            'X-BAPI-SIGN' => $sign,
            'X-BAPI-TIMESTAMP' => $ts,
            'X-BAPI-RECV-WINDOW' => $recvWindow,
            'X-BAPI-SIGN-TYPE' => '2', // HMAC-SHA256
            'Accept' => 'application/json',
        ];
    }

    /**
     * GET /v5/user/query-api çağrısı ile anahtar doğrulama.
     */
    public static function queryApi(string $base, string $apiKey, string $secret, int $recvWindow = 15000): array
    {
        $ts = (string) self::serverTime($base);
        $rw = (string) $recvWindow;
        $query = ''; // /v5/user/query-api ek parametre istemiyor
        $sign = self::signV5($ts, $apiKey, $rw, $secret, $query);
        $headers = self::headers($apiKey, $sign, $ts, $rw);
        $endpoint = rtrim($base, '/').'/v5/user/query-api';

        try {
            $res = Http::timeout(15)->withHeaders($headers)->get($endpoint);

            return [
                'http' => $res->status(),
                'json' => (array) $res->json(),
                'meta' => compact('ts', 'rw', 'sign', 'endpoint'),
            ];
        } catch (\Throwable $e) {
            return [
                'http' => 0,
                'json' => ['retCode' => -1, 'retMsg' => $e->getMessage()],
                'meta' => compact('ts', 'rw', 'sign', 'endpoint'),
            ];
        }
    }
}
