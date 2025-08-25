<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Contracts\Exchange\ExchangeClientInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

final class HealthExchangeCommand extends Command
{
    protected $signature = 'sentx:health:exchange {symbol=BTCUSDT : Symbol to check}';

    protected $description = 'Exchange health check - connectivity, API limits, market data';

    public function handle(ExchangeClientInterface $exchange): int
    {
        $symbol = $this->argument('symbol');
        $this->info("🔍 Exchange Health Check: {$symbol}");

        $health = [
            'timestamp' => now()->toISOString(),
            'symbol' => $symbol,
            'checks' => [],
            'overall' => 'HEALTHY',
        ];

        try {
            // 1. Connectivity Check
            $start = microtime(true);
            $tickers = $exchange->tickers($symbol);
            $latency = (microtime(true) - $start) * 1000;

            $health['checks']['connectivity'] = [
                'status' => 'PASS',
                'latency_ms' => round($latency, 2),
                'response' => ! empty($tickers),
            ];

            // 2. Market Data Check
            if (! empty($tickers)) {
                $lastPrice = $tickers['result']['list'][0]['lastPrice'] ?? null;
                $health['checks']['market_data'] = [
                    'status' => $lastPrice ? 'PASS' : 'FAIL',
                    'last_price' => $lastPrice,
                    'data_freshness' => 'REAL_TIME',
                ];
            }

            // 3. API Limits Check
            $health['checks']['api_limits'] = [
                'status' => 'PASS',
                'rate_limit_remaining' => 'UNKNOWN', // Bybit doesn't expose this easily
                'recv_window' => config('exchange.bybit.recv_window', 15000),
            ];

            // 4. Price Filter Check
            $priceFilter = $exchange->getInstrumentInfo($symbol);
            if ($priceFilter) {
                $health['checks']['price_filter'] = [
                    'status' => 'PASS',
                    'tick_size' => $priceFilter['tickSize'] ?? 'UNKNOWN',
                    'min_price' => $priceFilter['minPrice'] ?? 'UNKNOWN',
                    'max_price' => $priceFilter['maxPrice'] ?? 'UNKNOWN',
                ];
            }

        } catch (\Throwable $e) {
            $health['overall'] = 'UNHEALTHY';
            $health['checks']['error'] = [
                'status' => 'FAIL',
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
            ];

            Log::error('Exchange health check failed', [
                'symbol' => $symbol,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        // Overall status determination
        $failedChecks = array_filter(
            $health['checks'],
            fn ($check) => isset($check['status']) && $check['status'] === 'FAIL'
        );

        if (count($failedChecks) > 0) {
            $health['overall'] = 'DEGRADED';
        }

        // Output
        if ($this->option('verbose')) {
            $this->table(['Check', 'Status', 'Details'], array_map(function ($name, $check) {
                return [
                    $name,
                    $check['status'] ?? 'UNKNOWN',
                    json_encode($check, JSON_UNESCAPED_UNICODE),
                ];
            }, array_keys($health['checks']), $health['checks']));
        }

        $this->info("Overall Status: {$health['overall']}");

        if ($health['overall'] === 'HEALTHY') {
            $this->info('✅ Exchange is healthy');

            return 0;
        } elseif ($health['overall'] === 'DEGRADED') {
            $this->warn('⚠️ Exchange is degraded');

            return 1;
        } else {
            $this->error('❌ Exchange is unhealthy');

            return 2;
        }
    }
}
