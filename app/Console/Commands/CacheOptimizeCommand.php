<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;

final class CacheOptimizeCommand extends Command
{
    protected $signature = 'sentx:cache-optimize {--clear : Clear all caches} {--warm : Warm up caches}';

    protected $description = 'Cache optimization ve management';

    public function handle(): int
    {
        if ($this->option('clear')) {
            $this->clearAllCaches();
        }

        if ($this->option('warm')) {
            $this->warmUpCaches();
        }

        if (! $this->option('clear') && ! $this->option('warm')) {
            $this->showCacheStatus();
        }

        return self::SUCCESS;
    }

    private function clearAllCaches(): void
    {
        $this->info('Clearing all caches...');

        Cache::flush();
        Artisan::call('config:clear');
        Artisan::call('route:clear');
        Artisan::call('view:clear');

        $this->info('✅ All caches cleared');
    }

    private function warmUpCaches(): void
    {
        $this->info('Warming up caches...');

        // Config cache
        Artisan::call('config:cache');

        // Route cache
        Artisan::call('route:cache');

        // View cache
        Artisan::call('view:cache');

        $this->info('✅ Caches warmed up');
    }

    private function showCacheStatus(): void
    {
        $this->info('Cache Status:');

        // Test cache performance
        $start = microtime(true);
        Cache::put('test:performance', 'value', 1);
        Cache::get('test:performance');
        Cache::forget('test:performance');
        $cacheTime = (microtime(true) - $start) * 1000;

        $this->line("Cache Performance: {$cacheTime}ms");
        $this->line('Config Cached: '.(file_exists(base_path('bootstrap/cache/config.php')) ? 'Yes' : 'No'));
        $this->line('Routes Cached: '.(file_exists(base_path('bootstrap/cache/routes-v7.php')) ? 'Yes' : 'No'));
        $this->line('Views Cached: '.(file_exists(base_path('bootstrap/cache/packages.php')) ? 'Yes' : 'No'));
    }
}
