<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Monitoring Configuration
    |--------------------------------------------------------------------------
    |
    | Bu dosya monitoring ve alerting servisleri için konfigürasyon
    | parametrelerini içerir.
    |
    */

    'health_check' => [
        'enabled' => env('MONITORING_HEALTH_CHECK_ENABLED', true),
        'interval' => env('MONITORING_HEALTH_CHECK_INTERVAL', 60), // seconds
        'timeout' => env('MONITORING_HEALTH_CHECK_TIMEOUT', 30), // seconds
    ],

    'alerts' => [
        'enabled' => env('MONITORING_ALERTS_ENABLED', true),
        'retention_days' => env('MONITORING_ALERTS_RETENTION_DAYS', 30),
        'max_active_alerts' => env('MONITORING_MAX_ACTIVE_ALERTS', 100),
    ],

    'thresholds' => [
        'memory_usage' => env('MONITORING_MEMORY_THRESHOLD', 80), // percentage
        'cpu_load' => env('MONITORING_CPU_THRESHOLD', 5.0),
        'disk_usage' => env('MONITORING_DISK_THRESHOLD', 85), // percentage
        'exchange_latency' => env('MONITORING_EXCHANGE_LATENCY_THRESHOLD', 1000), // ms
    ],

    'notifications' => [
        'channels' => [
            'email' => env('MONITORING_EMAIL_ENABLED', true),
            'slack' => env('MONITORING_SLACK_ENABLED', false),
            'webhook' => env('MONITORING_WEBHOOK_ENABLED', false),
        ],
        'webhook_url' => env('MONITORING_WEBHOOK_URL'),
        'slack_webhook' => env('MONITORING_SLACK_WEBHOOK'),
    ],
];
