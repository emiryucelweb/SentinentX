<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Alert;
use App\Services\Monitoring\AlertService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AlertServiceTest extends TestCase
{
    use RefreshDatabase;

    private function makeDispatcher(): object
    {
        // Test double: sözleşmeyi uygular ve dispatch destekler
        return new class implements \App\Contracts\Notifier\AlertDispatcher
        {
            public array $sent = [];

            public function send(string $level, string $code, string $message, array $context = [], ?string $dedupKey = null): void
            {
                $this->sent[] = compact('level', 'code', 'message', 'context', 'dedupKey');
            }

            public function dispatch(Alert $alert): void
            {
                $this->sent[] = ['dispatched' => $alert->toArray()];
            }
        };
    }

    #[Test]
    public function create_system_alert_persists_and_dispatches(): void
    {
        $dispatcher = $this->makeDispatcher();
        $service = new AlertService($dispatcher);

        $alert = $service->createSystemAlert('exchange', 'Down', 'critical');

        $this->assertInstanceOf(Alert::class, $alert);
        $this->assertSame('system.exchange', $alert->type);
        $this->assertSame('critical', $alert->severity);
        $this->assertSame('active', $alert->status);

        $this->assertNotEmpty($dispatcher->sent);
    }
}
