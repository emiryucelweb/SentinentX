<?php

declare(strict_types=1);

namespace App\Services\Notifier;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class TelegramNotifier
{
    private ?string $token;

    private ?string $chatId;

    public function __construct()
    {
        $this->token = Config::get('notifier.telegram.bot_token');
        $this->chatId = Config::get('notifier.telegram.chat_id');
    }

    public function notify(string $text): void
    {
        $t = $this->token;
        $c = $this->chatId;

        if (! $t || ! $c || $t === '...' || $c === '...') {
            Log::notice('Telegram notify skipped (token/chatId missing/invalid)');

            return;
        }

        $url = "https://api.telegram.org/bot{$t}/sendMessage";

        try {
            $resp = Http::timeout(5)->asForm()->post($url, [
                'chat_id' => $c,
                'text' => $text,
                'parse_mode' => 'HTML',
                'disable_web_page_preview' => true,
            ]);
            if ($resp->failed()) {
                Log::warning('Telegram notify failed', ['status' => $resp->status(), 'body' => $resp->body()]);
            }
        } catch (\Throwable $e) {
            Log::notice('Telegram notify exception suppressed', ['err' => $e->getMessage()]);
        }
    }

    /**
     * Alias for notify method for backward compatibility
     */
    public function send(string $text): void
    {
        $this->notify($text);
    }
}
