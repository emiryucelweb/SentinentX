<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Health\LiveHealthCheckService;
use Illuminate\Console\Command;

class HealthCheckCommand extends Command
{
    protected $signature = 'sentx:health-check 
                           {--check= : Specific check to run (telegram, exchange, websocket, sentiment, queue, database, cache, filesystem)}
                           {--json : Output as JSON}
                           {--minimal : Minimal output}';

    protected $description = 'Run comprehensive live health checks on all system components';

    public function handle(LiveHealthCheckService $healthCheck): int
    {
        $specificCheck = $this->option('check');
        $jsonOutput = $this->option('json');
        $minimal = $this->option('minimal');

        if (! $minimal) {
            $this->info('🏥 Starting SentientX Live Health Checks...');
            $this->newLine();
        }

        if ($specificCheck) {
            $result = $healthCheck->runSpecificCheck($specificCheck);

            if ($jsonOutput) {
                $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

                return self::SUCCESS;
            }

            return $this->displaySpecificResult($specificCheck, $result, $minimal);
        }

        // Run all checks
        $results = $healthCheck->runAllChecks();

        if ($jsonOutput) {
            $this->line(json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            return self::SUCCESS;
        }

        return $this->displayAllResults($results, $minimal);
    }

    private function displaySpecificResult(string $checkName, array $result, bool $minimal): int
    {
        $status = $result['status'] ?? 'unknown';
        $duration = $result['duration_ms'] ?? 0;

        $statusIcon = match ($status) {
            'healthy' => '✅',
            'degraded' => '⚠️',
            'error' => '❌',
            default => '❓'
        };

        if (! $minimal) {
            $this->line("{$statusIcon} <comment>{$checkName}</comment>: {$status} ({$duration}ms)");

            if ($status === 'error' && isset($result['error'])) {
                $this->error("   Error: {$result['error']}");
            }

            if (isset($result['details']) && ! empty($result['details'])) {
                $this->line('   Details:');
                foreach ($result['details'] as $key => $value) {
                    $valueStr = is_bool($value) ? ($value ? 'true' : 'false') : (string) $value;
                    $this->line("     {$key}: {$valueStr}");
                }
            }
        }

        return $status === 'healthy' ? self::SUCCESS : self::FAILURE;
    }

    private function displayAllResults(array $results, bool $minimal): int
    {
        $overallStatus = $results['overall_status'] ?? 'unknown';
        $healthPercentage = $results['health_percentage'] ?? 0;
        $duration = $results['duration_ms'] ?? 0;
        $checks = $results['checks'] ?? [];

        if (! $minimal) {
            // Header
            $statusIcon = match ($overallStatus) {
                'healthy' => '✅',
                'degraded' => '⚠️',
                'unhealthy' => '❌',
                default => '❓'
            };

            $this->line("<info>🎯 Overall Health: {$statusIcon} {$overallStatus} ({$healthPercentage}% healthy)</info>");
            $this->line("<comment>⏱️ Total Duration: {$duration}ms</comment>");
            $this->newLine();

            // Individual checks
            $this->line('<info>📋 Individual Checks:</info>');
            foreach ($checks as $checkName => $result) {
                $status = $result['status'] ?? 'unknown';
                $checkDuration = $result['duration_ms'] ?? 0;

                $statusIcon = match ($status) {
                    'healthy' => '✅',
                    'degraded' => '⚠️',
                    'error' => '❌',
                    default => '❓'
                };

                $this->line("  {$statusIcon} <comment>{$checkName}</comment>: {$status} ({$checkDuration}ms)");

                if ($status === 'error' && isset($result['error'])) {
                    $this->line("     <fg=red>Error: {$result['error']}</>");
                }
            }

            $this->newLine();

            // Summary
            $summary = $results['summary'] ?? [];
            $this->line('<info>📊 Summary:</info>');
            $this->line('  Total Checks: '.($summary['total'] ?? 0));
            $this->line('  Healthy: '.($summary['healthy'] ?? 0));
            $this->line('  Unhealthy: '.($summary['unhealthy'] ?? 0));
            $this->line("  Health Percentage: {$healthPercentage}%");
        } else {
            // Quiet mode - just status
            $this->line("{$overallStatus}:{$healthPercentage}%");
        }

        return $overallStatus === 'healthy' ? self::SUCCESS :
               ($overallStatus === 'degraded' ? 1 : self::FAILURE);
    }
}
