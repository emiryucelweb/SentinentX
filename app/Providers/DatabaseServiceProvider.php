<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Database\Events\ConnectionEstablished;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

/**
 * Database Service Provider for timeout and connection management
 */
class DatabaseServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Set PostgreSQL specific timeouts when connection is established
        Event::listen(ConnectionEstablished::class, function (ConnectionEstablished $event) {
            $connection = $event->connection;
            $config = $connection->getConfig();

            if ($config['driver'] === 'pgsql') {
                $this->configurePostgreSQLTimeouts($connection, $config);
            }

            if ($config['driver'] === 'mysql') {
                $this->configureMySQLTimeouts($connection, $config);
            }
        });

        // Log slow queries (for debugging and optimization)
        DB::listen(function ($query) {
            if ($query->time > 1000) { // Log queries slower than 1 second
                Log::warning('Slow database query detected', [
                    'sql' => $query->sql,
                    'bindings' => $query->bindings,
                    'time_ms' => $query->time,
                    'connection' => $query->connectionName,
                ]);
            }
        });
    }

    /**
     * Configure PostgreSQL specific timeout settings
     */
    private function configurePostgreSQLTimeouts($connection, array $config): void
    {
        try {
            // Set statement timeout
            if (isset($config['statement_timeout'])) {
                $connection->statement("SET statement_timeout = '{$config['statement_timeout']}'");
            }

            // Set lock timeout
            if (isset($config['lock_timeout'])) {
                $connection->statement("SET lock_timeout = '{$config['lock_timeout']}'");
            }

            // Set idle in transaction timeout
            if (isset($config['idle_in_transaction_session_timeout'])) {
                $connection->statement("SET idle_in_transaction_session_timeout = '{$config['idle_in_transaction_session_timeout']}'");
            }

            // Enable log_min_duration_statement for development
            if (app()->environment('local', 'testing')) {
                $connection->statement('SET log_min_duration_statement = 1000'); // Log queries > 1s
            }

            Log::debug('PostgreSQL timeouts configured', [
                'connection' => $connection->getName(),
                'statement_timeout' => $config['statement_timeout'] ?? 'default',
                'lock_timeout' => $config['lock_timeout'] ?? 'default',
                'idle_timeout' => $config['idle_in_transaction_session_timeout'] ?? 'default',
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to configure PostgreSQL timeouts', [
                'connection' => $connection->getName(),
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Configure MySQL specific timeout settings
     */
    private function configureMySQLTimeouts($connection, array $config): void
    {
        try {
            // Set wait_timeout and interactive_timeout
            $timeout = $config['options'][PDO::ATTR_TIMEOUT] ?? 30;
            $connection->statement("SET SESSION wait_timeout = {$timeout}");
            $connection->statement("SET SESSION interactive_timeout = {$timeout}");

            // Set innodb_lock_wait_timeout
            $connection->statement('SET SESSION innodb_lock_wait_timeout = 10');

            Log::debug('MySQL timeouts configured', [
                'connection' => $connection->getName(),
                'timeout' => $timeout,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to configure MySQL timeouts', [
                'connection' => $connection->getName(),
                'error' => $e->getMessage(),
            ]);
        }
    }
}
