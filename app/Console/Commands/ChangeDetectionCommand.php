<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

final class ChangeDetectionCommand extends Command
{
    protected $signature = 'sentx:change-detect {--path= : Path to monitor} {--clear : Clear change history}';

    protected $description = 'File change detection ve monitoring';

    private const CACHE_KEY = 'change_detection:history';

    private const DEFAULT_PATHS = ['app', 'config', 'routes', 'database'];

    public function handle(): int
    {
        $path = $this->option('path') ?: implode(',', self::DEFAULT_PATHS);
        $paths = explode(',', $path);

        if ($this->option('clear')) {
            $this->clearChangeHistory();

            return self::SUCCESS;
        }

        $changes = $this->detectChanges($paths);
        $this->displayChanges($changes);

        return self::SUCCESS;
    }

    /**
     * @param array<string> $paths
     * @return array<string, array{status: string, previous_hash: mixed, current_hash: string, detected_at: string}>
     */
    private function detectChanges(array $paths): array
    {
        $changes = [];
        $history = Cache::get(self::CACHE_KEY, []);

        foreach ($paths as $path) {
            $path = trim($path);
            if (! File::exists($path)) {
                continue;
            }

            $currentHash = $this->calculatePathHash($path);
            $previousHash = $history[$path] ?? null;

            if ($previousHash !== null && $previousHash !== $currentHash) {
                $changes[$path] = [
                    'status' => 'changed',
                    'previous_hash' => $previousHash,
                    'current_hash' => $currentHash,
                    'detected_at' => now()->toISOString() ?? '',
                ];
            }

            $history[$path] = $currentHash;
        }

        Cache::put(self::CACHE_KEY, $history, 86400); // 24 saat

        return $changes;
    }

    private function calculatePathHash(string $path): string
    {
        if (File::isFile($path)) {
            $hash = md5_file($path);
            return $hash !== false ? $hash : '';
        }

        if (File::isDirectory($path)) {
            $files = File::allFiles($path);
            $hashes = [];

            foreach ($files as $file) {
                $hashes[] = md5_file($file->getPathname());
            }

            return md5(implode('', $hashes));
        }

        return '';
    }

    /**
     * @param array<string, array{status: string, previous_hash: mixed, current_hash: string, detected_at: string}> $changes
     */
    private function displayChanges(array $changes): void
    {
        if (empty($changes)) {
            $this->info('✅ No changes detected');

            return;
        }

        $this->warn('🔍 Changes detected:');

        foreach ($changes as $path => $change) {
            $this->line("📁 {$path}");
            $this->line("   Status: {$change['status']}");
            $this->line("   Detected: {$change['detected_at']}");
            $this->line('');
        }
    }

    private function clearChangeHistory(): void
    {
        Cache::forget(self::CACHE_KEY);
        $this->info('✅ Change history cleared');
    }
}
