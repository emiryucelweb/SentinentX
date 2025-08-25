<?php

namespace Tests\Concerns;

use Illuminate\Support\Facades\Redis;
use Throwable;

trait RequiresRedis
{
    /**
     * Skip test if Redis is not available
     */
    protected function requireRedis(): void
    {
        try {
            Redis::connection()->ping();
        } catch (Throwable $e) {
            $this->markTestSkipped('Redis not available in test environment: '.$e->getMessage());
        }
    }

    /**
     * Clean Redis before test if available
     */
    protected function cleanRedis(): void
    {
        try {
            Redis::connection()->flushall();
        } catch (Throwable $e) {
            // Redis not available, skip cleaning
        }
    }

    /**
     * Check if Redis is available without throwing
     */
    protected function isRedisAvailable(): bool
    {
        try {
            Redis::connection()->ping();

            return true;
        } catch (Throwable $e) {
            return false;
        }
    }
}
