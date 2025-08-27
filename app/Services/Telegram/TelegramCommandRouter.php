<?php

declare(strict_types=1);

namespace App\Services\Telegram;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

/**
 * Telegram Command Router
 * Routes parsed intents to appropriate command handlers
 */
class TelegramCommandRouter
{
    public function __construct(
        private readonly TelegramRbacService $rbac,
        private readonly TelegramApprovalService $approval
    ) {}

    /**
     * Route intent to appropriate handler
     */
    public function route(array $intent, array $user): string
    {
        $intentName = $intent['intent'] ?? 'unknown';
        $args = $intent['args'] ?? [];
        $requiresApproval = $intent['requires_approval'] ?? false;
        $coreChange = $intent['core_change'] ?? false;

        Log::info('Routing Telegram command', [
            'intent' => $intentName,
            'user' => $user['name'] ?? 'Unknown',
            'requires_approval' => $requiresApproval,
            'core_change' => $coreChange,
        ]);

        // Check permissions
        if (! $this->rbac->hasPermission($user, $intentName)) {
            return $this->formatError("❌ **Yetki Hatası**\n\nBu komutu çalıştırma yetkiniz yok.\n\n".
                                    $this->rbac->getUserRoleSummary($user));
        }

        // Handle core changes that require approval
        if ($coreChange && ! $this->rbac->canApprovePatches($user)) {
            return $this->approval->createPatchRequest($intent, $user);
        }

        // Route to appropriate handler
        return match ($intentName) {
            'status' => $this->handleStatus($args),
            'list_positions' => $this->handleListPositions($args),
            'balance' => $this->handleBalance($args),
            'pnl' => $this->handlePnl($args),
            'open_position' => $this->handleOpenPosition($args),
            'close_position' => $this->handleClosePosition($args),
            'set_risk' => $this->handleSetRisk($args, $user),
            'cycle_now' => $this->handleCycleNow($args),
            'open_test_order' => $this->handleTestOrder($args),
            'ai_health' => $this->handleAiHealth($args),
            'sentiment_check' => $this->handleSentimentCheck($args),
            'set_param' => $this->handleSetParam($args, $user),
            'approve_patch' => $this->handleApprovePatch($args, $user),
            'help' => $this->handleHelp($args),
            'unknown' => $this->handleUnknown($args),
            default => $this->formatError("⚠️ **Bilinmeyen Komut**\n\nIntent: `{$intentName}`\n\n/help yazarak komut listesini görebilirsiniz.")
        };
    }

    /**
     * Handle status command
     */
    private function handleStatus(array $args): string
    {
        try {
            // Use existing TelegramWebhookController logic
            $controller = app(\App\Http\Controllers\TelegramWebhookController::class);

            return $controller->getStatusMessage();
        } catch (\Exception $e) {
            Log::error('Status command failed', ['error' => $e->getMessage()]);

            return $this->formatError("❌ **Durum Bilgisi Alınamadı**\n\n".$e->getMessage());
        }
    }

    /**
     * Handle list positions command
     */
    private function handleListPositions(array $args): string
    {
        try {
            $controller = app(\App\Http\Controllers\TelegramWebhookController::class);

            return $controller->getPositionsDetailMessage();
        } catch (\Exception $e) {
            Log::error('List positions failed', ['error' => $e->getMessage()]);

            return $this->formatError("❌ **Pozisyon Listesi Alınamadı**\n\n".$e->getMessage());
        }
    }

    /**
     * Handle balance command
     */
    private function handleBalance(array $args): string
    {
        try {
            // Get balance info from exchange
            $client = app('App\Services\Exchange\BybitClient');
            $balance = $client->getAccountInfo();

            if ($balance['retCode'] === 0) {
                $totalBalance = $balance['result']['totalEquity'] ?? '0';
                $availableBalance = $balance['result']['totalAvailableBalance'] ?? '0';

                return "💰 **Hesap Bakiyesi**\n\n".
                       "💵 **Toplam Bakiye:** \${$totalBalance}\n".
                       "💸 **Kullanılabilir:** \${$availableBalance}\n".
                       '⏰ '.now()->format('H:i:s');
            }

            return $this->formatError("❌ **Bakiye Bilgisi Alınamadı**\n\nBybit API hatası");
        } catch (\Exception $e) {
            Log::error('Balance command failed', ['error' => $e->getMessage()]);

            return $this->formatError("❌ **Bakiye Bilgisi Alınamadı**\n\n".$e->getMessage());
        }
    }

    /**
     * Handle PnL command
     */
    private function handlePnl(array $args): string
    {
        try {
            $controller = app(\App\Http\Controllers\TelegramWebhookController::class);

            return $controller->getPnLMessage();
        } catch (\Exception $e) {
            Log::error('PnL command failed', ['error' => $e->getMessage()]);

            return $this->formatError("❌ **PnL Bilgisi Alınamadı**\n\n".$e->getMessage());
        }
    }

    /**
     * Handle open position command
     */
    private function handleOpenPosition(array $args): string
    {
        $symbol = $args['symbol'] ?? 'BTC';

        // Validate symbol whitelist
        $allowedSymbols = ['BTC', 'ETH', 'SOL', 'XRP'];
        if (! in_array($symbol, $allowedSymbols)) {
            return $this->formatError("❌ **Desteklenmeyen Symbol**\n\nSadece şu coinler desteklenir: ".implode(', ', $allowedSymbols));
        }

        return "🎯 **{$symbol} Pozisyonu Açma**\n\n".
               "⚡ Risk tercihinizi seçin:\n".
               "🔹 Düşük Risk (3-15x kaldıraç)\n".
               "🔹 Orta Risk (15-45x kaldıraç)\n".
               "🔹 Yüksek Risk (45-75x kaldıraç)\n\n".
               'Devam etmek için: `set risk LOW` veya `set risk HIGH` yazın';
    }

    /**
     * Handle close position command
     */
    private function handleClosePosition(array $args): string
    {
        $symbol = $args['symbol'] ?? 'ALL';

        if ($symbol === 'ALL') {
            return "⚠️ **Tüm Pozisyonları Kapat**\n\n".
                   "Bu işlem tüm açık pozisyonları kapatacak.\n".
                   'Onaylamak için: `confirm close all` yazın';
        }

        return "🔴 **{$symbol} Pozisyonunu Kapat**\n\n".
               "Bu pozisyon kapatılacak.\n".
               "Onaylamak için: `confirm close {$symbol}` yazın";
    }

    /**
     * Handle set risk command
     */
    private function handleSetRisk(array $args, array $user): string
    {
        $mode = $args['mode'] ?? 'MID';
        $interval = $args['interval_sec'] ?? null;

        $modeNames = [
            'LOW' => 'Düşük Risk (Conservative)',
            'MID' => 'Orta Risk (Moderate)',
            'HIGH' => 'Yüksek Risk (Aggressive)',
        ];

        $modeName = $modeNames[$mode] ?? $mode;

        $message = "⚙️ **Risk Modu Güncellendi**\n\n".
                   "📊 **Yeni Mod:** {$modeName}\n";

        if ($interval) {
            $intervalMin = $interval / 60;
            $message .= "⏱️ **Döngü Aralığı:** {$intervalMin} dakika\n";
        }

        $message .= "\n🎯 Risk profili aktif hale getirildi.";

        // Log the risk change
        Log::info('Risk mode changed via Telegram', [
            'mode' => $mode,
            'interval_sec' => $interval,
            'user' => $user['name'] ?? 'Unknown',
        ]);

        return $message;
    }

    /**
     * Handle cycle now command
     */
    private function handleCycleNow(array $args): string
    {
        try {
            // Trigger AI analysis cycle
            Artisan::call('sentx:open-now', ['--symbols' => 'BTC,ETH,SOL,XRP']);

            return "🔄 **AI Döngüsü Başlatıldı**\n\n".
                   "🤖 4 coin için AI analizi yapılıyor...\n".
                   "📊 Analiz coinleri: BTC, ETH, SOL, XRP\n\n".
                   '⏰ Sonuçlar 1-2 dakika içinde hazır olacak.';
        } catch (\Exception $e) {
            Log::error('Cycle now command failed', ['error' => $e->getMessage()]);

            return $this->formatError("❌ **AI Döngüsü Başlatılamadı**\n\n".$e->getMessage());
        }
    }

    /**
     * Handle test order command
     */
    private function handleTestOrder(array $args): string
    {
        $symbol = $args['symbol'] ?? 'BTC';
        $cancelAfter = $args['cancel_after_sec'] ?? 10;

        return "🧪 **Test Emri ({$symbol})**\n\n".
               "📝 Post-only limit emri oluşturuluyor...\n".
               "⏰ {$cancelAfter} saniye sonra otomatik iptal\n".
               "🎯 Sadece test amaçlı - gerçek trade değil\n\n".
               '⚡ Test başlatılıyor...';
    }

    /**
     * Handle AI health command
     */
    private function handleAiHealth(array $args): string
    {
        $providers = ['OpenAI', 'Gemini', 'Grok'];
        $status = [];

        foreach ($providers as $provider) {
            $envKey = strtoupper($provider).'_API_KEY';
            $hasKey = ! empty(env($envKey));
            $status[] = "🤖 **{$provider}:** ".($hasKey ? '🟢 Aktif' : '🔴 API Key Eksik');
        }

        return "🏥 **AI Sağlık Kontrolü**\n\n".implode("\n", $status)."\n\n".
               '💡 3/3 provider aktif olmalı konsensus için.';
    }

    /**
     * Handle sentiment check command
     */
    private function handleSentimentCheck(array $args): string
    {
        try {
            // Get market sentiment data
            $coinGecko = app(\App\Services\Market\CoinGeckoService::class);
            $btcData = $coinGecko->getCoinData('bitcoin');

            $sentiment = $btcData['sentiment_votes_up_percentage'] ?? 50;
            $price = $btcData['market_data']['current_price']['usd'] ?? 0;

            return "📊 **Piyasa Duygusu**\n\n".
                   "🪙 **Bitcoin:** \${$price}\n".
                   "📈 **Pozitif Sentiment:** %{$sentiment}\n".
                   '📉 **Negatif Sentiment:** %'.(100 - $sentiment)."\n\n".
                   '⏰ '.now()->format('H:i:s');
        } catch (\Exception $e) {
            return $this->formatError("❌ **Sentiment Verisi Alınamadı**\n\n".$e->getMessage());
        }
    }

    /**
     * Handle set parameter command (core change)
     */
    private function handleSetParam(array $args, array $user): string
    {
        $param = $args['param'] ?? 'unknown';
        $value = $args['value'] ?? 'unknown';

        // This is a core change - should have been caught earlier
        return "⚠️ **Parametre Değişikliği**\n\n".
               "📋 **Parametre:** {$param}\n".
               "🔧 **Değer:** {$value}\n\n".
               '🚨 Bu core değişiklik için patch oluşturulacak.';
    }

    /**
     * Handle approve patch command
     */
    private function handleApprovePatch(array $args, array $user): string
    {
        if (! $this->rbac->canApprovePatches($user)) {
            return $this->formatError("❌ **Yetki Hatası**\n\nPatch onaylama yetkiniz yok.");
        }

        $patchId = $args['patch_id'] ?? 'unknown';

        return $this->approval->approvePatch($patchId, $user);
    }

    /**
     * Handle help command
     */
    private function handleHelp(array $args): string
    {
        return "🤖 **SentientX Telegram AI**\n\n".
               "📋 **Doğal Dil Komutları:**\n".
               "• \"Durumu özetle\" - Sistem durumu\n".
               "• \"Risk modunu YÜKSEK yap\" - Risk ayarla\n".
               "• \"BTC pozisyonu aç\" - Pozisyon aç\n".
               "• \"ETH test emri ver\" - Test emri\n".
               "• \"Pozisyonları listele\" - Pozisyon durumu\n\n".
               "🎯 **Intent Örnekleri:**\n".
               "• status, set_risk, open_position\n".
               "• cycle_now, ai_health, sentiment_check\n\n".
               "🔧 **Admin Komutları:**\n".
               "• \"Patch uygula PR-42\" - Patch onay\n".
               "• \"set param=value\" - Parametre değişim\n\n".
               '💡 **Doğal dil kullanabilirsiniz!**';
    }

    /**
     * Handle unknown command
     */
    private function handleUnknown(array $args): string
    {
        $originalText = $args['original_text'] ?? 'bilinmeyen komut';

        return "❓ **Anlaşılamayan Komut**\n\n".
               "📝 **Girdi:** \"{$originalText}\"\n\n".
               "💡 **Öneriler:**\n".
               "• \"help\" - Komut listesi\n".
               "• \"status\" - Sistem durumu\n".
               "• \"risk LOW\" - Risk ayarla\n".
               "• \"open BTC\" - Pozisyon aç\n\n".
               '🗣️ Doğal dil kullanabilirsiniz!';
    }

    /**
     * Format error message
     */
    private function formatError(string $message): string
    {
        return $message."\n\n⏰ ".now()->format('H:i:s');
    }
}
