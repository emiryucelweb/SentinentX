<?php

namespace App\Jobs;

use App\Services\CycleRunner;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;

class RunSymbolCycle implements ShouldQueue
{
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public string $symbol;

    public int $tries = 2;

    public array $backoff = [5, 15];

    public function __construct(string $symbol)
    {
        $this->symbol = $symbol;
        $this->onQueue('trade');
    }

    public function uniqueId(): string
    {
        return 'symbol:'.$this->symbol;
    }

    public function middleware(): array
    {
        return [new WithoutOverlapping($this->uniqueId())];
    }

    public function handle(CycleRunner $runner): void
    {
        $runner->runSymbol($this->symbol);
    }
}
