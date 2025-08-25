<?php

declare(strict_types=1);

namespace App\Services\Lock;

use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;

final class LockService
{
    /** @return bool lock alındı mı */
    public function acquire(string $key, int $ttlSeconds, callable $fn): bool
    {
        $lock = Cache::lock($key, $ttlSeconds);
        try {
            return $lock->block(1, function () use ($fn, $lock) {
                try {
                    $fn();

                    return true;
                } finally {
                    optional($lock)->release();
                }
            });
        } catch (LockTimeoutException) {
            return false;
        }
    }
}
