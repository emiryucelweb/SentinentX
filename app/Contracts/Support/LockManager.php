<?php

namespace App\Contracts\Support;

interface LockManager
{
    /** @return mixed */
    public function acquire(string $key, int $seconds, callable $callback);
}
