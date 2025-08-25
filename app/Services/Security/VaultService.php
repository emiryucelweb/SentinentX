<?php

declare(strict_types=1);

namespace App\Services\Security;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * HashiCorp Vault entegrasyonu
 * Secrets, API keys, ve hassas verileri güvenli şekilde yönetir
 */
class VaultService
{
    private string $vaultUrl;

    private string $token;

    private int $defaultTtl;

    public function __construct()
    {
        $this->vaultUrl = rtrim(config('vault.url', 'http://127.0.0.1:8200'), '/');
        $this->token = config('vault.token', '');
        $this->defaultTtl = config('vault.cache_ttl', 300); // 5 minutes
    }

    /**
     * Vault'tan secret okur
     */
    public function getSecret(string $path, ?string $key = null, bool $useCache = true): mixed
    {
        $cacheKey = "vault_secret_{$path}".($key ? "_{$key}" : '');

        if ($useCache && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        try {
            $response = Http::withToken($this->token)
                ->timeout(10)
                ->get("{$this->vaultUrl}/v1/{$path}");

            if (! $response->successful()) {
                throw new RuntimeException("Vault request failed: {$response->status()}");
            }

            $data = $response->json('data.data', []);

            if ($key) {
                $value = $data[$key] ?? null;
                if ($value === null) {
                    throw new RuntimeException("Key '{$key}' not found in secret '{$path}'");
                }
            } else {
                $value = $data;
            }

            if ($useCache) {
                Cache::put($cacheKey, $value, $this->defaultTtl);
            }

            return $value;
        } catch (\Exception $e) {
            Log::error('Vault secret retrieval failed', [
                'path' => $path,
                'key' => $key,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Vault'a secret yazar
     */
    public function putSecret(string $path, array $data): bool
    {
        try {
            $response = Http::withToken($this->token)
                ->timeout(10)
                ->put("{$this->vaultUrl}/v1/{$path}", [
                    'data' => $data,
                ]);

            if (! $response->successful()) {
                throw new RuntimeException("Vault write failed: {$response->status()}");
            }

            // Cache'i temizle
            $cacheKey = "vault_secret_{$path}";
            Cache::forget($cacheKey);

            // Alt key'lerin cache'ini de temizle
            foreach (array_keys($data) as $key) {
                Cache::forget("{$cacheKey}_{$key}");
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Vault secret write failed', [
                'path' => $path,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Vault'tan secret siler
     */
    public function deleteSecret(string $path): bool
    {
        try {
            $response = Http::withToken($this->token)
                ->timeout(10)
                ->delete("{$this->vaultUrl}/v1/{$path}");

            if (! $response->successful()) {
                throw new RuntimeException("Vault delete failed: {$response->status()}");
            }

            // Cache'i temizle
            $cacheKey = "vault_secret_{$path}";
            Cache::forget($cacheKey);

            return true;
        } catch (\Exception $e) {
            Log::error('Vault secret deletion failed', [
                'path' => $path,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * API key'leri Vault'tan güvenli şekilde alır
     */
    public function getApiKey(string $service): string
    {
        return $this->getSecret("secret/api-keys/{$service}", 'key');
    }

    /**
     * Database credentials'ı Vault'tan alır
     */
    public function getDatabaseCredentials(string $connection = 'default'): array
    {
        return $this->getSecret("secret/database/{$connection}");
    }

    /**
     * Encryption key'leri Vault'tan alır
     */
    public function getEncryptionKey(string $purpose): string
    {
        return $this->getSecret("secret/encryption/{$purpose}", 'key');
    }

    /**
     * Trading-specific secrets
     */
    public function getTradingSecrets(): array
    {
        return [
            'bybit_api_key' => $this->getSecret('secret/trading/bybit', 'api_key'),
            'bybit_secret' => $this->getSecret('secret/trading/bybit', 'secret'),
            'webhook_secret' => $this->getSecret('secret/trading/webhooks', 'secret'),
        ];
    }

    /**
     * Vault health check
     */
    public function healthCheck(): array
    {
        try {
            $response = Http::withToken($this->token)
                ->timeout(5)
                ->get("{$this->vaultUrl}/v1/sys/health");

            $healthy = $response->successful();

            return [
                'healthy' => $healthy,
                'status' => $response->status(),
                'response_time' => $response->transferStats?->getTransferTime() ?? 0,
                'vault_url' => $this->vaultUrl,
            ];
        } catch (\Exception $e) {
            return [
                'healthy' => false,
                'error' => $e->getMessage(),
                'vault_url' => $this->vaultUrl,
            ];
        }
    }

    /**
     * Cache'i temizle
     */
    public function clearCache(?string $path = null): void
    {
        if ($path) {
            Cache::forget("vault_secret_{$path}");
        } else {
            // Tüm vault cache'ini temizle
            Cache::flush(); // Bu agresif, production'da dikkatli kullan
        }
    }
}
