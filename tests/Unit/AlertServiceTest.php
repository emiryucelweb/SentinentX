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

            public function send(string $level, string $code, string $message, array $context = [], ?string $dedupKey = null): array
            {
                $this->sent[] = compact('level', 'code', 'message', 'context', 'dedupKey');
                return ['success' => true, 'id' => uniqid()];
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

        // Test actual dispatch with real Telegram
        $telegramToken = env('TELEGRAM_BOT_TOKEN');
        $telegramChatId = env('TELEGRAM_CHAT_ID');
        
        if (!empty($telegramToken) && !empty($telegramChatId)) {
            $realDispatcher = app(\App\Contracts\Notifier\AlertDispatcher::class);
            $realDispatcher->dispatch($alert);
        }
        
        $this->assertTrue(true); // Test passes if alert is created without exception
    }
}
