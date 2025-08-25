<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Vault Configuration
    |--------------------------------------------------------------------------
    |
    | HashiCorp Vault entegrasyon ayarları
    |
    */

    'url' => env('VAULT_URL', 'http://127.0.0.1:8200'),

    'token' => env('VAULT_TOKEN', ''),

    'cache_ttl' => env('VAULT_CACHE_TTL', 300), // 5 minutes

    'timeout' => env('VAULT_TIMEOUT', 10), // seconds

    /*
    |--------------------------------------------------------------------------
    | Secret Paths
    |--------------------------------------------------------------------------
    |
    | Vault'ta kullanılan standart secret path'leri
    |
    */
    'paths' => [
        'api_keys' => 'secret/api-keys',
        'database' => 'secret/database',
        'encryption' => 'secret/encryption',
        'trading' => 'secret/trading',
        'webhooks' => 'secret/webhooks',
        'monitoring' => 'secret/monitoring',
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Settings
    |--------------------------------------------------------------------------
    |
    | Vault güvenlik ayarları
    |
    */
    'security' => [
        'verify_ssl' => env('VAULT_VERIFY_SSL', true),
        'max_retries' => env('VAULT_MAX_RETRIES', 3),
        'retry_delay' => env('VAULT_RETRY_DELAY', 1000), // milliseconds
    ],
];
