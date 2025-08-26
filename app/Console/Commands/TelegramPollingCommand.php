<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Http\Controllers\TelegramWebhookController;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramPollingCommand extends Command
{
    protected $signature = 'telegram:polling';

    protected $description = 'Start Telegram bot polling for messages';

    private int $lastUpdateId = 0;

    public function handle(): int
    {
        $this->info('🚀 Telegram polling başlatılıyor...');

        $botToken = config('notifier.telegram.bot_token');
        $webhookController = app(TelegramWebhookController::class);

        $this->info('Bot Token: '.substr($botToken, 0, 10).'...');

        while (true) {
            try {
                $this->info('📡 Telegram updates kontrol ediliyor...');

                $response = Http::timeout(5)->get("https://api.telegram.org/bot{$botToken}/getUpdates", [
                    'offset' => $this->lastUpdateId + 1,
                    'limit' => 10,
                    'timeout' => 5,
                ]);

                $this->info('Response status: '.$response->status());

                if ($response->successful()) {
                    $data = $response->json();

                    $this->info('Updates alındı: '.count($data['result'] ?? []));

                    if (isset($data['result']) && ! empty($data['result'])) {
                        foreach ($data['result'] as $update) {
                            $this->info('Update işleniyor: '.json_encode($update));
                            $this->processUpdate($update, $webhookController);
                            $this->lastUpdateId = $update['update_id'];
                        }
                    }
                } else {
                    $this->error('API error: '.$response->body());
                }

                sleep(2); // 2 saniye bekle

            } catch (\Exception $e) {
                $this->error('Telegram polling error: '.$e->getMessage());
                Log::error('Telegram polling error: '.$e->getMessage());
                sleep(5); // Hata durumunda 5 saniye bekle
            }
        }

        return 0;
    }

    private function processUpdate(array $update, TelegramWebhookController $controller): void
    {
        // Hem message hem de channel_post'u kontrol et
        $message = $update['message'] ?? $update['channel_post'] ?? null;

        if ($message) {
            $text = $message['text'] ?? '';
            $chatId = $message['chat']['id'] ?? null;
            $allowedChatId = config('notifier.telegram.chat_id');

            // Sadece belirlenen chat ID'den kabul et
            if ($chatId != $allowedChatId) {
                return;
            }

            $this->info("📨 Mesaj işleniyor: {$text}");
            Log::info('Telegram polling received', ['message' => $message]);

            $response = $controller->processCommand($text);

            $this->info('📤 Cevap: '.($response ? substr($response, 0, 50).'...' : 'null'));

            if ($response) {
                $this->sendTelegramMessage((string) $chatId, $response);
                $this->info('✅ Cevap gönderildi!');
            }
        }
    }

    private function sendTelegramMessage(string $chatId, string $text): void
    {
        $botToken = config('notifier.telegram.bot_token');

        Http::post("https://api.telegram.org/bot{$botToken}/sendMessage", [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'HTML',
        ]);
    }
}
