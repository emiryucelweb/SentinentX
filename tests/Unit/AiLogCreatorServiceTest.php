<?php

namespace Tests\Unit;

use App\DTO\AiDecision;
use App\Models\AiLog;
use App\Services\Logger\AiLogCreatorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AiLogCreatorServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_log_persists_record(): void
    {
        $svc = new AiLogCreatorService;
        $d = new AiDecision('HOLD', 50, null, null, null, 'check');
        $svc->log(\Illuminate\Support\Str::uuid(), 'BTCUSDT', 'openai', 'STAGE1', $d, ['symbol' => 'BTCUSDT'], ['raw' => 'ok'], 123);

        $this->assertDatabaseCount('ai_logs', 1);
        $rec = AiLog::first();
        $this->assertSame('openai', $rec->provider);
        $this->assertSame(123, $rec->latency_ms);
    }
}
