<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Security\VaultService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class VaultCommand extends Command
{
    protected $signature = 'sentx:vault
                            {action : Action to perform (get|put|delete|health|list)}
                            {path? : Secret path}
                            {--key= : Specific key to retrieve}
                            {--data= : JSON data to put (for put action)}
                            {--no-cache : Skip cache when getting secrets}';

    protected $description = 'Manage HashiCorp Vault secrets';

    private VaultService $vaultService;

    public function __construct(VaultService $vaultService)
    {
        parent::__construct();
        $this->vaultService = $vaultService;
    }

    public function handle(): int
    {
        $action = $this->argument('action');
        $path = $this->argument('path');

        try {
            switch ($action) {
                case 'get':
                    return $this->handleGet($path);

                case 'put':
                    return $this->handlePut($path);

                case 'delete':
                    return $this->handleDelete($path);

                case 'health':
                    return $this->handleHealth();

                case 'list':
                    return $this->handleList();

                default:
                    $this->error("Unknown action: {$action}");
                    $this->line('Available actions: get, put, delete, health, list');

                    return self::FAILURE;
            }
        } catch (\Exception $e) {
            $this->error("Vault operation failed: {$e->getMessage()}");
            Log::error('Vault command failed', [
                'action' => $action,
                'path' => $path,
                'error' => $e->getMessage(),
            ]);

            return self::FAILURE;
        }
    }

    private function handleGet(string $path): int
    {
        if (! $path) {
            $this->error('Path is required for get action');

            return self::FAILURE;
        }

        $key = $this->option('key');
        $useCache = ! $this->option('no-cache');

        $secret = $this->vaultService->getSecret($path, $key, $useCache);

        if (is_array($secret)) {
            $this->table(['Key', 'Value'], collect($secret)->map(fn ($v, $k) => [$k, $v])->toArray());
        } else {
            $this->info("Value: {$secret}");
        }

        return self::SUCCESS;
    }

    private function handlePut(string $path): int
    {
        if (! $path) {
            $this->error('Path is required for put action');

            return self::FAILURE;
        }

        $dataOption = $this->option('data');
        if (! $dataOption) {
            $this->error('--data option is required for put action');
            $this->line('Example: --data=\'{"key1":"value1","key2":"value2"}\'');

            return self::FAILURE;
        }

        $data = json_decode($dataOption, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->error('Invalid JSON data: '.json_last_error_msg());

            return self::FAILURE;
        }

        $this->vaultService->putSecret($path, $data);
        $this->info("Secret successfully written to: {$path}");

        return self::SUCCESS;
    }

    private function handleDelete(string $path): int
    {
        if (! $path) {
            $this->error('Path is required for delete action');

            return self::FAILURE;
        }

        if (! $this->confirm("Are you sure you want to delete secret at '{$path}'?")) {
            $this->info('Operation cancelled');

            return self::SUCCESS;
        }

        $this->vaultService->deleteSecret($path);
        $this->info("Secret successfully deleted from: {$path}");

        return self::SUCCESS;
    }

    private function handleHealth(): int
    {
        $health = $this->vaultService->healthCheck();

        $this->table(['Property', 'Value'], [
            ['Status', $health['healthy'] ? '✅ Healthy' : '❌ Unhealthy'],
            ['URL', $health['vault_url']],
            ['HTTP Status', $health['status'] ?? 'N/A'],
            ['Response Time', isset($health['response_time']) ? round($health['response_time'] * 1000, 2).'ms' : 'N/A'],
            ['Error', $health['error'] ?? 'None'],
        ]);

        return $health['healthy'] ? self::SUCCESS : self::FAILURE;
    }

    private function handleList(): int
    {
        $paths = config('vault.paths', []);

        $this->info('Configured Vault paths:');
        $this->table(['Name', 'Path'], collect($paths)->map(fn ($path, $name) => [$name, $path])->toArray());

        $this->newLine();
        $this->info('Common commands:');
        $this->line('  php artisan sentx:vault get secret/trading/bybit --key=api_key');
        $this->line('  php artisan sentx:vault put secret/api-keys/openai --data=\'{"key":"sk-..."}\'');
        $this->line('  php artisan sentx:vault health');

        return self::SUCCESS;
    }
}
