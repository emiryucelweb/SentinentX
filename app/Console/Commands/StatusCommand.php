<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\LabRun;
use App\Models\Trade;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class StatusCommand extends Command
{
    protected $signature = 'sentx:status
                          {--json : Output in JSON format}
                          {--detailed : Show detailed information}';

    protected $description = 'Show system status and health information';

    public function handle(): int
    {
        $data = $this->gatherSystemInfo();

        if ($this->option('json')) {
            $this->line((string) json_encode($data, JSON_PRETTY_PRINT));
        } else {
            $this->displayTable($data);
        }

        return 0;
    }

    /**
     * @return array<string, mixed>
     */
    private function gatherSystemInfo(): array
    {
        $appVersion = config('app.version', 'unknown');
        $laravelVersion = app()->version();
        $phpVersion = PHP_VERSION;
        $environment = app()->environment();

        // Database status
        try {
            DB::connection()->getPdo();
            $dbStatus = 'connected';
            $dbDriver = DB::connection()->getDriverName();
        } catch (\Exception $e) {
            $dbStatus = 'error: '.$e->getMessage();
            $dbDriver = 'unknown';
        }

        // Cache status
        try {
            Cache::put('status_test', 'ok', 1);
            $cacheStatus = Cache::get('status_test') === 'ok' ? 'working' : 'error';
        } catch (\Exception $e) {
            $cacheStatus = 'error: '.$e->getMessage();
        }

        // Trading stats
        $totalTrades = Trade::count();
        $openTrades = Trade::where('status', 'OPEN')->count();
        $recentTrades = Trade::where('created_at', '>=', now()->subDay())->count();

        // Lab stats
        $totalLabRuns = LabRun::count();
        $recentLabRuns = LabRun::where('created_at', '>=', now()->subDay())->count();

        $data = [
            'system' => [
                'app_version' => $appVersion,
                'laravel_version' => $laravelVersion,
                'php_version' => $phpVersion,
                'environment' => $environment,
                'timestamp' => now()->toISOString(),
            ],
            'database' => [
                'status' => $dbStatus,
                'driver' => $dbDriver,
            ],
            'cache' => [
                'status' => $cacheStatus,
            ],
            'trading' => [
                'total_trades' => $totalTrades,
                'open_trades' => $openTrades,
                'recent_trades_24h' => $recentTrades,
            ],
            'lab' => [
                'total_runs' => $totalLabRuns,
                'recent_runs_24h' => $recentLabRuns,
            ],
        ];

        if ($this->option('detailed')) {
            $data['system']['memory_usage'] = memory_get_usage(true);
            $data['system']['memory_peak'] = memory_get_peak_usage(true);
            $data['system']['load_average'] = sys_getloadavg();
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function displayTable(array $data): void
    {
        $this->info('SentientX System Status');
        $this->line('');

        // System info
        $this->comment('System Information:');
        $this->table(
            ['Property', 'Value'],
            [
                ['App Version', $data['system']['app_version']],
                ['Laravel Version', $data['system']['laravel_version']],
                ['PHP Version', $data['system']['php_version']],
                ['Environment', $data['system']['environment']],
                ['Timestamp', $data['system']['timestamp']],
            ]
        );

        // Service status
        $this->comment('Service Status:');
        $this->table(
            ['Service', 'Status'],
            [
                ['Database', $data['database']['status']],
                ['Cache', $data['cache']['status']],
            ]
        );

        // Trading stats
        $this->comment('Trading Statistics:');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Trades', $data['trading']['total_trades']],
                ['Open Trades', $data['trading']['open_trades']],
                ['Recent Trades (24h)', $data['trading']['recent_trades_24h']],
            ]
        );

        // Lab stats
        $this->comment('Lab Statistics:');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Lab Runs', $data['lab']['total_runs']],
                ['Recent Lab Runs (24h)', $data['lab']['recent_runs_24h']],
            ]
        );

        if ($this->option('detailed')) {
            $this->comment('System Resources:');
            $this->table(
                ['Resource', 'Value'],
                [
                    ['Memory Usage', number_format($data['system']['memory_usage'] / 1024 / 1024, 2).' MB'],
                    ['Memory Peak', number_format($data['system']['memory_peak'] / 1024 / 1024, 2).' MB'],
                    ['Load Average', implode(', ', $data['system']['load_average'])],
                ]
            );
        }
    }
}
