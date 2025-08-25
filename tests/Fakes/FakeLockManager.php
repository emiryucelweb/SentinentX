<?php

namespace Tests\Fakes;

use App\Contracts\Support\LockManager;

final class FakeLockManager implements LockManager
{
    public array $calls = [];

    public function acquire(string $key, int $seconds, callable $callback)
    {
        $this->calls[] = [$key, $seconds];

        return $callback();
    }
}
