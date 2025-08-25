<?php

namespace App\Services\Support;

use App\Contracts\Support\LockManager;
use Illuminate\Support\Facades\Cache;

final class CacheLockManager implements LockManager
{
    public function acquire(string $key, int $seconds, callable $callback)
    {
        $lock = Cache::lock($key, $seconds);

        return $lock->block(0, function () use ($callback, $lock) {
            try {
                return $callback();
            } finally {
                optional($lock)->release();
            }
        });
    }
}
