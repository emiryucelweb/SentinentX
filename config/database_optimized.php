<?php

/**
 * SQLite Performance Optimizations
 * Bu dosya SQLite için optimize edilmiş database configuration'ları içerir
 */

return [
    'sqlite_optimized' => [
        'driver' => 'sqlite',
        'url' => env('DATABASE_URL'),
        'database' => env('DB_DATABASE', database_path('database.sqlite')),
        'prefix' => '',
        'foreign_key_constraints' => env('DB_FOREIGN_KEYS', true),
        'options' => [
            // SQLite Performance Optimizations
            PDO::ATTR_TIMEOUT => 30,
            PDO::ATTR_EMULATE_PREPARES => true,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ],
        // SQLite Performance Pragmas
        'pragmas' => [
            'journal_mode' => 'WAL',           // Write-Ahead Logging for better concurrency
            'synchronous' => 'NORMAL',         // Balance between safety and performance
            'cache_size' => '-64000',          // 64MB cache (negative = KB)
            'temp_store' => 'memory',          // Keep temp tables in memory
            'mmap_size' => '268435456',        // 256MB memory-mapped I/O
            'foreign_keys' => 'on',            // Enable foreign key constraints
            'busy_timeout' => '30000',         // 30 second busy timeout
            'auto_vacuum' => 'incremental',    // Incremental auto-vacuum
        ],
    ],
];
