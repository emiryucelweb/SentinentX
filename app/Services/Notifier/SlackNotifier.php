<?php

declare(strict_types=1);

namespace App\Services\Notifier;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class SlackNotifier
{
    private ?string $webhook;

    public function __construct()
    {
        $this->webhook = Config::get('services.slack.webhook');
    }

    public function notify(string $text): void
    {
        $url = $this->webhook;

        // Env yoksa ya da placeholder ise sessizce geç
        if (! $url || $url === '...' || ! str_starts_with($url, 'http')) {
            Log::notice('Slack notify skipped (webhook missing/invalid)');

            return;
        }

        try {
            $resp = Http::timeout(5)->asJson()->post($url, ['text' => $text]);
            if ($resp->failed()) {
                Log::warning('Slack notify failed', ['status' => $resp->status(), 'body' => $resp->body()]);
            }
        } catch (\Throwable $e) {
            // Ağ/DNS hatası test/develop ortamında akışı kesmesin
            Log::notice('Slack notify exception suppressed', ['err' => $e->getMessage()]);
        }
    }
}
