<?php

declare(strict_types=1);

namespace App\Services\Telegram;

use Illuminate\Support\Facades\Log;

/**
 * Telegram Natural Language Intent Service
 * Converts natural language to structured intent JSON
 */
class TelegramIntentService
{
    /**
     * Parse natural language to intent JSON
     */
    public function parseIntent(string $naturalLanguage): array
    {
        $text = trim(strtolower($naturalLanguage));

        Log::info('Parsing Telegram intent', ['input' => $naturalLanguage]);

        // Rule-based intent parsing (fast and reliable)
        $intent = $this->parseWithRules($text);

        // If rule-based fails, use LLM fallback (optional - requires API key)
        if ($intent['intent'] === 'unknown' && config('ai.openai.enabled', false)) {
            $intent = $this->parseWithLLM($naturalLanguage);
        }

        Log::info('Intent parsed', ['intent' => $intent]);

        return $intent;
    }

    /**
     * Rule-based intent parsing (primary method)
     */
    private function parseWithRules(string $text): array
    {
        // Remove common prefixes
        $text = preg_replace('/^(\/|!)/', '', $text);
        $text = trim($text);

        // Status and monitoring commands
        if (preg_match('/^(status|durum|durumu.*özetle|sistem.*durum)/i', $text)) {
            return $this->createIntent('status');
        }

        // Position management
        if (preg_match('/^(positions|pozisyon|açık.*pozisyon|list.*position)/i', $text)) {
            return $this->createIntent('list_positions');
        }

        // Balance inquiry
        if (preg_match('/^(balance|bakiye|para.*durum|hesap.*durum)/i', $text)) {
            return $this->createIntent('balance');
        }

        // PnL inquiry
        if (preg_match('/^(pnl|kar.*zarar|profit.*loss|günlük.*kar)/i', $text)) {
            return $this->createIntent('pnl');
        }

        // Open position commands
        if (preg_match('/^(open|aç|pozisyon.*aç|trade)\s+(btc|eth|sol|xrp|bitcoin|ethereum|solana|ripple)/i', $text, $matches)) {
            $symbol = $this->normalizeSymbol($matches[2] ?? 'BTC');

            return $this->createIntent('open_position', ['symbol' => $symbol]);
        }

        // Close position commands
        if (preg_match('/^(close|kapat|pozisyon.*kapat)\s+(btc|eth|sol|xrp|all|hepsi)/i', $text, $matches)) {
            $symbol = $this->normalizeSymbol($matches[2] ?? 'ALL');

            return $this->createIntent('close_position', ['symbol' => $symbol]);
        }

        // Risk level setting
        if (preg_match('/^(risk|set.*risk|risk.*mode)\s+(low|mid|high|düşük|orta|yüksek|conservative|moderate|aggressive)\s*(?:(\d+)\s*(?:min|minute|dk|dakika))?/i', $text, $matches)) {
            $mode = $this->normalizeRiskMode($matches[2] ?? 'MID');
            $interval = isset($matches[3]) ? (int) $matches[3] * 60 : null; // Convert to seconds

            return $this->createIntent('set_risk', ['mode' => $mode, 'interval_sec' => $interval]);
        }

        // Cycle now command
        if (preg_match('/^(cycle|döngü|scan|tara|analiz.*yap|ai.*çalıştır)/i', $text)) {
            return $this->createIntent('cycle_now');
        }

        // Test order commands
        if (preg_match('/^(test.*order|test.*limit|deneme.*emir)\s+(btc|eth|sol|xrp)\s*(?:(\d+)\s*(?:sec|second|sn|saniye))?/i', $text, $matches)) {
            $symbol = $this->normalizeSymbol($matches[1] ?? 'BTC');
            $cancelAfter = isset($matches[3]) ? (int) $matches[3] : 10;

            return $this->createIntent('open_test_order', [
                'symbol' => $symbol,
                'post_only' => true,
                'cancel_after_sec' => $cancelAfter,
            ]);
        }

        // AI health check
        if (preg_match('/^(ai.*health|ai.*durum|ai.*sağlık|consensus.*durum)/i', $text)) {
            return $this->createIntent('ai_health');
        }

        // Sentiment check
        if (preg_match('/^(sentiment|piyasa.*duygus|sentiment.*check)/i', $text)) {
            return $this->createIntent('sentiment_check');
        }

        // Parameter setting (advanced)
        if (preg_match('/^(set|ayarla)\s+(\w+)\s*=\s*(.+)/i', $text, $matches)) {
            $param = trim($matches[2]);
            $value = trim($matches[3]);

            // Check if this is a core parameter (requires approval)
            $coreParams = ['risk_engine', 'scheduler_interval', 'leverage_limits', 'position_limits'];
            $isCoreChange = in_array($param, $coreParams);

            return $this->createIntent('set_param', [
                'param' => $param,
                'value' => $value,
            ], $isCoreChange);
        }

        // Patch application (admin only)
        if (preg_match('/^(apply.*patch|patch.*uygula|approve)\s+(pr-?\d+|patch-?\d+)/i', $text, $matches)) {
            $patchId = trim($matches[2]);

            return $this->createIntent('approve_patch', ['patch_id' => $patchId], true);
        }

        // Help command
        if (preg_match('/^(help|yardım|komut|nasıl)/i', $text)) {
            return $this->createIntent('help');
        }

        // Complex natural language patterns
        return $this->parseComplexPatterns($text);
    }

    /**
     * Parse complex natural language patterns
     */
    private function parseComplexPatterns(string $text): array
    {
        // "Risk modunu YÜKSEK yap, 2 dk aralıkla"
        if (preg_match('/risk.*mod.*(\w+).*(\d+)\s*(dk|dakika|min|minute)/i', $text, $matches)) {
            $mode = $this->normalizeRiskMode($matches[1]);
            $interval = (int) $matches[2] * 60; // Convert to seconds

            return $this->createIntent('set_risk', ['mode' => $mode, 'interval_sec' => $interval]);
        }

        // "ETH için test limit ver, 10 sn sonra iptal"
        if (preg_match('/(btc|eth|sol|xrp).*test.*limit.*(\d+)\s*(sn|saniye|sec|second)/i', $text, $matches)) {
            $symbol = $this->normalizeSymbol($matches[1]);
            $cancelAfter = (int) $matches[2];

            return $this->createIntent('open_test_order', [
                'symbol' => $symbol,
                'post_only' => true,
                'cancel_after_sec' => $cancelAfter,
            ]);
        }

        // "Durumu özetle"
        if (preg_match('/durum.*özetle|özet.*ver|sistem.*nasıl/i', $text)) {
            return $this->createIntent('status');
        }

        // Unknown intent
        return $this->createIntent('unknown', ['original_text' => $text]);
    }

    /**
     * LLM-based intent parsing (fallback)
     */
    private function parseWithLLM(string $text): array
    {
        // This would use OpenAI API to parse complex natural language
        // For now, return unknown to keep it simple
        Log::info('LLM intent parsing not implemented yet', ['text' => $text]);

        return $this->createIntent('unknown', ['original_text' => $text, 'requires_llm' => true]);
    }

    /**
     * Create structured intent object
     */
    private function createIntent(string $intent, array $args = [], bool $coreChange = false): array
    {
        // Determine if approval is required
        $requiresApproval = $coreChange || in_array($intent, [
            'approve_patch', 'set_param', 'apply_patch',
        ]);

        return [
            'intent' => $intent,
            'args' => $args,
            'core_change' => $coreChange,
            'requires_approval' => $requiresApproval,
            'timestamp' => now()->toISOString(),
        ];
    }

    /**
     * Normalize symbol names
     */
    private function normalizeSymbol(string $symbol): string
    {
        $symbol = strtoupper(trim($symbol));

        $mapping = [
            'BITCOIN' => 'BTC',
            'ETHEREUM' => 'ETH',
            'SOLANA' => 'SOL',
            'RIPPLE' => 'XRP',
            'ALL' => 'ALL',
            'HEPSI' => 'ALL',
        ];

        return $mapping[$symbol] ?? $symbol;
    }

    /**
     * Normalize risk mode names
     */
    private function normalizeRiskMode(string $mode): string
    {
        $mode = strtoupper(trim($mode));

        $mapping = [
            'DÜŞÜK' => 'LOW',
            'ORTA' => 'MID',
            'YÜKSEK' => 'HIGH',
            'CONSERVATIVE' => 'LOW',
            'MODERATE' => 'MID',
            'AGGRESSIVE' => 'HIGH',
        ];

        return $mapping[$mode] ?? $mode;
    }

    /**
     * Get demo intent examples for testing
     */
    public function getDemoIntents(): array
    {
        return [
            // Status examples
            '"Durumu özetle"' => '{"intent":"status"}',
            '"sistem nasıl?"' => '{"intent":"status"}',

            // Risk setting examples
            '"Risk modunu YÜKSEK yap, 2 dk aralıkla"' => '{"intent":"set_risk","args":{"mode":"HIGH","interval_sec":120}}',
            '"risk LOW"' => '{"intent":"set_risk","args":{"mode":"LOW"}}',

            // Position examples
            '"ETH için test limit ver, 10 sn sonra iptal"' => '{"intent":"open_test_order","args":{"symbol":"ETH","post_only":true,"cancel_after_sec":10}}',
            '"open BTC"' => '{"intent":"open_position","args":{"symbol":"BTC"}}',

            // Admin examples (requires approval)
            '"Patch\'i uygula: PR-42"' => '{"intent":"approve_patch","args":{"patch_id":"PR-42"},"requires_approval":true}',
            '"set leverage_limit = 50"' => '{"intent":"set_param","args":{"param":"leverage_limit","value":"50"},"core_change":true}',
        ];
    }
}
