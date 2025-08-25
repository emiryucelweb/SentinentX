<?php

/**
 * SaaS Multi-Tenant Database Configuration
 * Production-grade PostgreSQL setup for SaaS platform
 */

return [
    // Primary PostgreSQL Connection (Multi-tenant)
    'pgsql_saas' => [
        'driver' => 'pgsql',
        'url' => env('DATABASE_URL'),
        'host' => env('DB_HOST', '127.0.0.1'),
        'port' => env('DB_PORT', '5432'),
        'database' => env('DB_DATABASE', 'sentientx_saas'),
        'username' => env('DB_USERNAME', 'sentientx_user'),
        'password' => env('DB_PASSWORD', 'sentx_secure_2025'),
        'charset' => 'utf8',
        'prefix' => '',
        'prefix_indexes' => true,
        'search_path' => 'public',
        'sslmode' => 'prefer',

        // SaaS Performance Optimizations
        'options' => [
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_STRINGIFY_FETCHES => false,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_TIMEOUT => 30,
        ],

        // Connection Pool Settings
        'pool' => [
            'size' => 20,
            'timeout' => 30,
            'idle_timeout' => 300,
        ],
    ],

    // Read Replica for Analytics & Reporting
    'pgsql_replica' => [
        'driver' => 'pgsql',
        'host' => env('DB_REPLICA_HOST', '127.0.0.1'),
        'port' => env('DB_REPLICA_PORT', '5432'),
        'database' => env('DB_DATABASE', 'sentientx_saas'),
        'username' => env('DB_REPLICA_USERNAME', 'sentientx_user'),
        'password' => env('DB_REPLICA_PASSWORD', 'sentx_secure_2025'),
        'charset' => 'utf8',
        'prefix' => '',
        'prefix_indexes' => true,
        'search_path' => 'public',
        'sslmode' => 'prefer',
        'read_timeout' => 60,
    ],

    // MongoDB for Unstructured Data (AI Logs, Analytics)
    'mongodb_analytics' => [
        'driver' => 'mongodb',
        'host' => env('MONGODB_HOST', '127.0.0.1'),
        'port' => env('MONGODB_PORT', 27017),
        'database' => env('MONGODB_DATABASE', 'sentientx_analytics'),
        'username' => env('MONGODB_USERNAME', ''),
        'password' => env('MONGODB_PASSWORD', ''),
        'options' => [
            'appName' => 'SentientX',
            'retryWrites' => true,
            'w' => 'majority',
        ],
    ],

    // Redis Configuration for Caching & Sessions
    'redis_cluster' => [
        'client' => env('REDIS_CLIENT', 'predis'),
        'cluster' => [
            [
                'host' => env('REDIS_HOST', '127.0.0.1'),
                'port' => env('REDIS_PORT', 6379),
                'password' => env('REDIS_PASSWORD', null),
                'database' => 0,
            ],
        ],
        'options' => [
            'cluster' => 'redis',
            'prefix' => env('REDIS_PREFIX', 'sentx:'),
        ],
    ],
];
