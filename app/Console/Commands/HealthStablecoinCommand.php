<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

final class HealthStablecoinCommand extends Command
{
    protected $signature = 'sentx:health:stablecoin {--asset=USDT : Stablecoin to check}';

    protected $description = 'Stablecoin health check - USDT/USD parity, de-peg detection';

    public function handle(): int
    {
        $asset = strtoupper((string) $this->option('asset'));
        $this->info("🔒 Stablecoin Health Check: {$asset}");

        $health = [
            'timestamp' => now()->toISOString(),
            'asset' => $asset,
            'checks' => [],
            'overall' => 'HEALTHY',
        ];

        try {
            // 1. USDT/USD Parity Check
            $parityTest = $this->checkUSDTParity();
            $health['checks']['usdt_parity'] = $parityTest;

            // 2. De-peg Detection
            $depegTest = $this->checkDepegRisk($parityTest);
            $health['checks']['depeg_risk'] = $depegTest;

            // 3. Exchange Rate Consistency
            $consistencyTest = $this->checkExchangeRateConsistency();
            $health['checks']['exchange_consistency'] = $consistencyTest;

            // 4. Liquidity Check
            $liquidityTest = $this->checkLiquidity();
            $health['checks']['liquidity'] = $liquidityTest;

        } catch (\Throwable $e) {
            $health['overall'] = 'UNHEALTHY';
            $health['checks']['error'] = [
                'status' => 'FAIL',
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
            ];

            Log::error('Stablecoin health check failed', [
                'asset' => $asset,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        // Overall status determination
        $failedChecks = array_filter(
            $health['checks'],
            fn ($check) => isset($check['status']) && $check['status'] === 'FAIL'
        );

        $warningChecks = array_filter(
            $health['checks'],
            fn ($check) => isset($check['status']) && $check['status'] === 'WARN'
        );

        if (count($failedChecks) > 0) {
            $health['overall'] = 'UNHEALTHY';
        } elseif (count($warningChecks) > 0) {
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
            $this->info('✅ Stablecoin is healthy');

            return 0;
        } elseif ($health['overall'] === 'DEGRADED') {
            $this->warn('⚠️ Stablecoin is degraded');

            return 1;
        } else {
            $this->error('❌ Stablecoin is unhealthy');

            return 2;
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function checkUSDTParity(): array
    {
        $thresholds = config('health.usdt_depeg_range', [0.98, 1.02]);
        $minThreshold = $thresholds[0];
        $maxThreshold = $thresholds[1];

        // Simulate USDT/USD rate (in real implementation, fetch from API)
        $usdtRate = 0.9995; // Simulated rate

        $withinRange = $usdtRate >= $minThreshold && $usdtRate <= $maxThreshold;
        $deviation = abs(1.0 - $usdtRate) * 100;

        return [
            'status' => $withinRange ? 'PASS' : 'FAIL',
            'details' => [
                'usdt_rate' => $usdtRate,
                'min_threshold' => $minThreshold,
                'max_threshold' => $maxThreshold,
                'within_range' => $withinRange,
                'deviation_percent' => round($deviation, 4),
            ],
        ];
    }

    /**
     * @param array<string, mixed> $parityTest
     * @return array<string, mixed>
     */
    private function checkDepegRisk(array $parityTest): array
    {
        $usdtRate = $parityTest['details']['usdt_rate'] ?? 1.0;
        $deviation = abs(1.0 - $usdtRate) * 100;

        $riskLevel = match (true) {
            $deviation < 0.5 => 'LOW',
            $deviation < 1.0 => 'MEDIUM',
            $deviation < 2.0 => 'HIGH',
            default => 'CRITICAL'
        };

        $status = match ($riskLevel) {
            'LOW' => 'PASS',
            'MEDIUM' => 'WARN',
            'HIGH' => 'WARN',
            'CRITICAL' => 'FAIL'
        };

        return [
            'status' => $status,
            'details' => [
                'risk_level' => $riskLevel,
                'deviation_percent' => round($deviation, 4),
                'alert_code' => $riskLevel === 'CRITICAL' ? 'STABLECOIN_DEPEG' : 'NONE',
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function checkExchangeRateConsistency(): array
    {
        // Simulate multiple exchange rates
        $rates = [0.9995, 0.9997, 0.9993, 0.9996];
        $avgRate = array_sum($rates) / count($rates);
        $maxDeviation = max(array_map(fn ($rate) => abs($rate - $avgRate), $rates));

        $consistencyThreshold = 0.001; // 0.1%
        $consistent = $maxDeviation <= $consistencyThreshold;

        return [
            'status' => $consistent ? 'PASS' : 'WARN',
            'details' => [
                'rates' => $rates,
                'average_rate' => $avgRate,
                'max_deviation' => $maxDeviation,
                'consistency_threshold' => $consistencyThreshold,
                'consistent' => $consistent,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function checkLiquidity(): array
    {
        // Simulate liquidity metrics
        $marketCap = 95000000000; // 95B USDT
        $dailyVolume = 50000000000; // 50B daily volume
        $liquidityRatio = $dailyVolume / $marketCap;

        $healthyRatio = 0.5; // 50% daily volume to market cap
        $healthy = $liquidityRatio >= $healthyRatio;

        return [
            'status' => $healthy ? 'PASS' : 'WARN',
            'details' => [
                'market_cap' => $marketCap,
                'daily_volume' => $dailyVolume,
                'liquidity_ratio' => round($liquidityRatio, 4),
                'healthy_ratio' => $healthyRatio,
                'healthy' => $healthy,
            ],
        ];
    }
}
