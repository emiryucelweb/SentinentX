<?php

namespace Tests\Fakes;

use App\Contracts\Notifier\AlertDispatcher;

final class FakeAlertDispatcher implements AlertDispatcher
{
    /** @var array<int,array{level:string,code:string,message:string,context:array,dedupKey:?string}> */
    public array $sent = [];

    public function send(string $level, string $code, string $message, array $context = [], ?string $dedupKey = null): void
    {
        $this->sent[] = compact('level', 'code', 'message', 'context', 'dedupKey');
    }
}
