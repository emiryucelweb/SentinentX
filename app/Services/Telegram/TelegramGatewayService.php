<?php

declare(strict_types=1);

namespace App\Services\Telegram;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Telegram Gateway Service
 * Main orchestrator for Telegram AI system with RBAC and Intent Routing
 */
class TelegramGatewayService
{
    public function __construct(
        private readonly TelegramRbacService $rbac,
        private readonly TelegramIntentService $intentService,
        private readonly TelegramCommandRouter $router
    ) {}

    /**
     * Process incoming Telegram message with full AI pipeline
     */
    public function processMessage(string $chatId, string $text): string
    {
        try {
            // Rate limiting
            if (! $this->checkRateLimit($chatId)) {
                return "⚠️ **Rate Limit**\n\nÇok fazla komut gönderiyorsunuz. 1 dakika bekleyin.";
            }

            // 1. Authentication & Authorization (RBAC)
            $user = $this->rbac->authenticateUser($chatId);

            if (! $user) {
                Log::warning('Unauthorized Telegram access', ['chat_id' => $chatId, 'text' => substr($text, 0, 50)]);

                return "❌ **Yetkilendirme Hatası**\n\nBu bot'u kullanma yetkiniz yok.\n\nErişim için admin ile iletişime geçin.";
            }

            Log::info('Telegram message received', [
                'chat_id' => $chatId,
                'user' => $user['name'],
                'role' => $user['role'],
                'text' => substr($text, 0, 100),
            ]);

            // 2. Natural Language Intent Parsing
            $intent = $this->intentService->parseIntent($text);

            Log::info('Intent parsed', [
                'chat_id' => $chatId,
                'user' => $user['name'],
                'intent' => $intent['intent'],
                'requires_approval' => $intent['requires_approval'] ?? false,
            ]);

            // 3. Command Routing & Execution
            $response = $this->router->route($intent, $user);

            // 4. Audit Logging
            $this->auditLog($chatId, $user, $text, $intent, $response);

            return $response;

        } catch (\Exception $e) {
            Log::error('Telegram gateway error', [
                'chat_id' => $chatId,
                'text' => substr($text, 0, 100),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return "❌ **Sistem Hatası**\n\n".
                   "Komut işlenirken hata oluştu.\n".
                   "Hata tekrarlanırsa admin ile iletişime geçin.\n\n".
                   '⏰ '.now()->format('H:i:s');
        }
    }

    /**
     * Check rate limiting for user
     */
    private function checkRateLimit(string $chatId): bool
    {
        $key = "telegram_rate_limit:{$chatId}";
        $maxAttempts = 30; // 30 commands per minute
        $decayMinutes = 1;

        return RateLimiter::attempt($key, $maxAttempts, function () {
            return true;
        }, $decayMinutes * 60);
    }

    /**
     * Audit log for compliance and debugging
     */
    private function auditLog(string $chatId, array $user, string $text, array $intent, string $response): void
    {
        $auditData = [
            'timestamp' => now()->toISOString(),
            'chat_id' => $chatId,
            'user' => [
                'name' => $user['name'] ?? 'Unknown',
                'role' => $user['role'] ?? 'unknown',
            ],
            'input' => [
                'text' => $text,
                'length' => strlen($text),
            ],
            'intent' => [
                'name' => $intent['intent'] ?? 'unknown',
                'args' => $intent['args'] ?? [],
                'core_change' => $intent['core_change'] ?? false,
                'requires_approval' => $intent['requires_approval'] ?? false,
            ],
            'response' => [
                'length' => strlen($response),
                'preview' => substr($response, 0, 100),
            ],
        ];

        Log::info('Telegram command audit', $auditData);

        // Store in database for compliance (optional)
        try {
            \App\Models\AuditLog::create([
                'user_id' => null, // Telegram users don't have user_id
                'action' => 'telegram_command',
                'resource_type' => 'telegram_bot',
                'resource_id' => $chatId,
                'old_values' => null,
                'new_values' => $auditData,
                'ip_address' => 'telegram',
                'user_agent' => 'TelegramBot/1.0',
            ]);
        } catch (\Exception $e) {
            Log::warning('Failed to store audit log', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Get system capabilities summary for user
     */
    public function getCapabilitiesForUser(string $chatId): string
    {
        $user = $this->rbac->authenticateUser($chatId);

        if (! $user) {
            return "❌ **Yetkilendirme Hatası**\n\nBu bot'u kullanma yetkiniz yok.";
        }

        $role = $user['role'] ?? 'unknown';
        $canApprovePatches = $this->rbac->canApprovePatches($user);

        $message = "🤖 **SentientX Telegram AI**\n\n".
                   $this->rbac->getUserRoleSummary($user)."\n\n";

        // Role-specific capabilities
        if ($role === 'admin') {
            $message .= "🔧 **Admin Yetkileriniz:**\n".
                       "• Tüm komutlar kullanılabilir\n".
                       "• Risk profili değiştirebilir\n".
                       "• Pozisyon açabilir/kapatabilir\n".
                       "• Core sistem değişiklikleri yapabilir\n";

            if ($canApprovePatches) {
                $message .= "• Patch onaylayabilir\n";
            }
        } elseif ($role === 'operator') {
            $message .= "📊 **Operator Yetkileriniz:**\n".
                       "• Sistem durumu görüntüleme\n".
                       "• Pozisyon durumu görüntüleme\n".
                       "• Bakiye ve PnL görüntüleme\n".
                       "• Salt okunur erişim\n";
        }

        $message .= "\n💡 **Doğal Dil Desteği:**\n".
                   "• \"Durumu özetle\" - Sistem durumu\n".
                   "• \"BTC pozisyonu aç\" - Pozisyon aç\n".
                   "• \"Risk modunu yüksek yap\" - Risk ayarla\n\n".
                   '🚀 Komutlarınızı doğal dilde yazabilirsiniz!';

        return $message;
    }

    /**
     * Get demo intent examples with expected JSON output
     */
    public function getDemoIntents(): array
    {
        return $this->intentService->getDemoIntents();
    }

    /**
     * Test intent parsing (admin only)
     */
    public function testIntentParsing(string $chatId, string $testText): string
    {
        $user = $this->rbac->authenticateUser($chatId);

        if (! $user || $user['role'] !== 'admin') {
            return "❌ **Yetki Hatası**\n\nSadece admin'ler intent test edebilir.";
        }

        try {
            $intent = $this->intentService->parseIntent($testText);

            return "🧪 **Intent Test Sonucu**\n\n".
                   "📝 **Girdi:** \"{$testText}\"\n\n".
                   "🎯 **Çıktı:**\n".
                   "```json\n".
                   json_encode($intent, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE).
                   "\n```\n\n".
                   '✅ Parse başarılı: '.($intent['intent'] !== 'unknown' ? 'Evet' : 'Hayır');

        } catch (\Exception $e) {
            return "❌ **Intent Test Hatası**\n\n".
                   "📝 **Girdi:** \"{$testText}\"\n".
                   "⚠️ **Hata:** {$e->getMessage()}";
        }
    }

    /**
     * Add new admin user (existing admin only)
     */
    public function addAdmin(string $requesterId, string $newChatId, string $newName): string
    {
        $requester = $this->rbac->authenticateUser($requesterId);

        if (! $requester || $requester['role'] !== 'admin') {
            return "❌ **Yetki Hatası**\n\nSadece admin'ler yeni admin ekleyebilir.";
        }

        try {
            $this->rbac->addAdmin($newChatId, $newName);

            return "✅ **Yeni Admin Eklendi**\n\n".
                   "👤 **İsim:** {$newName}\n".
                   "🆔 **Chat ID:** {$newChatId}\n".
                   "🏷️ **Rol:** Admin\n".
                   "🔑 **Patch Approval:** Evet\n\n".
                   '🎉 Yeni admin artık tüm komutları kullanabilir!';

        } catch (\Exception $e) {
            return "❌ **Admin Ekleme Hatası**\n\n{$e->getMessage()}";
        }
    }

    /**
     * Add new operator user (admin only)
     */
    public function addOperator(string $requesterId, string $newChatId, string $newName): string
    {
        $requester = $this->rbac->authenticateUser($requesterId);

        if (! $requester || $requester['role'] !== 'admin') {
            return "❌ **Yetki Hatası**\n\nSadece admin'ler operator ekleyebilir.";
        }

        try {
            $this->rbac->addOperator($newChatId, $newName);

            return "✅ **Yeni Operator Eklendi**\n\n".
                   "👤 **İsim:** {$newName}\n".
                   "🆔 **Chat ID:** {$newChatId}\n".
                   "🏷️ **Rol:** Operator\n".
                   "📊 **Yetkiler:** Salt okunur\n\n".
                   '📋 Operator sadece durumu görüntüleyebilir.';

        } catch (\Exception $e) {
            return "❌ **Operator Ekleme Hatası**\n\n{$e->getMessage()}";
        }
    }
}
