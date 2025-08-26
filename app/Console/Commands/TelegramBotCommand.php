<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramBotCommand extends Command
{
    protected $signature = 'sentx:telegram-bot {--webhook : Setup webhook mode}';

    protected $description = 'Telegram bot komut işleyicisi';

    public function handle(): int
    {
        $isWebhook = $this->option('webhook');

        if ($isWebhook) {
            $this->info('🤖 Telegram Webhook mode - use this for production');
            $this->setupWebhook();

            return self::SUCCESS;
        }

        $this->info('🤖 Telegram Bot Manual Command Processor başlatılıyor...');
        $this->info('Komutlar: /scan, /status, /open SYMBOL, /risk SYMBOL');

        // Manual komut işleme döngüsü
        $this->processManualCommands();

        return self::SUCCESS;
    }

    private function setupWebhook(): void
    {
        $botToken = config('notifier.telegram.bot_token');
        $webhookUrl = config('app.url').'/api/telegram/webhook';

        $response = Http::post("https://api.telegram.org/bot{$botToken}/setWebhook", [
            'url' => $webhookUrl,
        ]);

        if ($response->successful()) {
            $this->info("✅ Webhook kuruldu: {$webhookUrl}");
        } else {
            $this->error('❌ Webhook kurulumu başarısız: '.$response->body());
        }
    }

    private function processManualCommands(): void
    {
        $botToken = config('notifier.telegram.bot_token');
        $chatId = config('notifier.telegram.chat_id');

        $this->line('');
        $this->line('💬 Mevcut komutlar:');
        $this->line('/scan - Tüm coinleri tara ve pozisyon aç');
        $this->line('/status - Sistem durumu');
        $this->line('/open BTCUSDT - Belirli coin için pozisyon aç');
        $this->line('/risk BTCUSDT - Risk profili seç');
        $this->line('');

        // Son mesaj ID'yi al
        $offset = 0;

        while (true) {
            try {
                // Yeni mesajları al
                $response = Http::get("https://api.telegram.org/bot{$botToken}/getUpdates", [
                    'offset' => $offset,
                    'timeout' => 10,
                ]);

                if (! $response->successful()) {
                    continue;
                }

                $updates = $response->json()['result'] ?? [];

                foreach ($updates as $update) {
                    $offset = $update['update_id'] + 1;
                    $message = $update['message'] ?? null;

                    if (! $message) {
                        continue;
                    }

                    $text = $message['text'] ?? '';
                    $fromChatId = $message['chat']['id'] ?? null;

                    // Sadece belirlenen chat ID'den kabul et
                    if ($fromChatId != $chatId) {
                        continue;
                    }

                    $this->line("📨 Komut alındı: {$text}");
                    $response = $this->processCommand($text);

                    // Cevabı gönder
                    if ($response) {
                        Http::post("https://api.telegram.org/bot{$botToken}/sendMessage", [
                            'chat_id' => $chatId,
                            'text' => $response,
                            'parse_mode' => 'HTML',
                        ]);
                    }
                }

                sleep(1);

            } catch (\Exception $e) {
                Log::error('Telegram bot error: '.$e->getMessage());
                sleep(5);
            }
        }
    }

    private function processCommand(string $text): ?string
    {
        $text = trim($text);

        if ($text === '/scan') {
            return $this->handleScanCommand();
        }

        if ($text === '/status') {
            return $this->handleStatusCommand();
        }

        if (preg_match('/^\/open\s+([A-Z0-9]+)$/i', $text, $matches)) {
            $symbol = strtoupper($matches[1]);
            if (! str_ends_with($symbol, 'USDT')) {
                $symbol .= 'USDT';
            }

            return $this->handleSingleCoinOpenCommand($symbol);
        }

        if (preg_match('/^\/risk\s+([A-Z0-9]+)$/i', $text, $matches)) {
            $symbol = strtoupper($matches[1]);
            if (! str_ends_with($symbol, 'USDT')) {
                $symbol .= 'USDT';
            }

            return $this->handleRiskCommand($symbol);
        }

        if ($text === '/help' || $text === '/start') {
            return $this->getHelpMessage();
        }

        return '❓ Bilinmeyen komut. /help yazarak komutları görebilirsin.';
    }

    private function handleScanCommand(): string
    {
        try {
            $this->call('sentx:lab-scan');

            return "🔍 <b>Lab scan başlatıldı!</b>\n\n📊 <b>Analiz edilecek coinler:</b>\n• BTCUSDT\n• ETHUSDT\n• SOLUSDT\n• XRPUSDT\n\n🎯 Uygun koşullarda tüm coinlerde pozisyon açılabilir.";
        } catch (\Exception $e) {
            return "❌ <b>Scan başlatılamadı:</b>\n".$e->getMessage();
        }
    }

    private function handleStatusCommand(): string
    {
        try {
            // Mevcut pozisyonları al
            $trades = \App\Models\Trade::where('status', 'OPEN')->get();

            if ($trades->isEmpty()) {
                return "📊 <b>Sistem Durumu</b>\n\n💰 Açık pozisyon: Yok\n⏰ Son scan: ".now()->format('H:i');
            }

            $message = "📊 <b>Sistem Durumu</b>\n\n";
            $message .= "💰 <b>Açık Pozisyonlar:</b>\n";

            foreach ($trades as $trade) {
                $pnl = $this->calculatePnL($trade);
                $pnlEmoji = $pnl > 0 ? '🟢' : ($pnl < 0 ? '🔴' : '⚪');

                $message .= "{$pnlEmoji} {$trade->symbol} {$trade->side} {$trade->leverage}x\n";
                $message .= "   Entry: \${$trade->entry_price} | P&L: ".number_format($pnl, 2)."%\n";
            }

            $message .= "\n⏰ Son güncellenme: ".now()->format('H:i');

            return $message;
        } catch (\Exception $e) {
            return '❌ Status alınamadı: '.$e->getMessage();
        }
    }

    private function handleSingleCoinOpenCommand(string $symbol): string
    {
        try {
            // Sadece tek coin için snapshot oluştur
            $snapshotPath = storage_path('app/snapshots/telegram_single_'.strtolower($symbol).'.json');
            $snapshot = [
                'timestamp' => now()->toISOString(),
                'symbols' => [$symbol], // Sadece bu coin
                'market_data' => [
                    $symbol => ['price' => 50000, 'change_24h' => 0.0],
                ],
                'portfolio' => [
                    'total_balance' => 10000,
                    'available_balance' => 9500,
                ],
                'risk_context' => [
                    'risk_profile' => 'Orta Risk',
                    'min_leverage' => 15,
                    'max_leverage' => 45,
                ],
            ];

            file_put_contents($snapshotPath, json_encode($snapshot, JSON_PRETTY_PRINT));

            $this->call('sentx:open-now', [
                'symbol' => $symbol,
                '--snapshot' => $snapshotPath,
                '--dry' => false,
            ]);

            return "🚀 <b>{$symbol} pozisyon açma başlatıldı!</b>\n\n💡 <i>Sadece {$symbol} analiz edildi.</i>";
        } catch (\Exception $e) {
            return "❌ {$symbol} pozisyon açılamadı: ".$e->getMessage();
        }
    }

    private function handleRiskCommand(string $symbol): string
    {
        return "⚙️ <b>{$symbol} Risk Profili</b>\n\n".
               "Risk profili ayarlamak için:\n".
               "<code>php artisan sentx:risk-profile {$symbol}</code>\n\n".
               "📊 <b>Risk Seviyeleri:</b>\n".
               "🟢 Düşük Risk: 3-15x kaldıraç\n".
               "🟡 Orta Risk: 15-45x kaldıraç\n".
               '🔴 Yüksek Risk: 45-125x kaldıraç';
    }

    private function getHelpMessage(): string
    {
        return "🤖 <b>SentinentX Telegram Bot</b>\n\n".
               "📋 <b>Mevcut Komutlar:</b>\n".
               "/scan - 4 coini tara, hepsinde pozisyon açabilir\n".
               "/status - Sistem durumu\n".
               "/open BTC - 4 coin analiz et, sadece BTC'de aç\n".
               "/open ETH - 4 coin analiz et, sadece ETH'de aç\n".
               "/risk SYMBOL - Risk profili bilgisi\n".
               "/help - Bu yardım mesajı\n\n".
               "⏰ <b>Otomatik:</b> Her 2 saatte bir /scan\n".
               '🎯 <b>AI:</b> Her zaman 4 coin analiz eder';
    }

    private function calculatePnL(\App\Models\Trade $trade): float
    {
        // Basit P&L hesaplama - gerçek implementation'da current price gerekli
        return rand(-500, 500) / 100; // Mock data
    }
}
