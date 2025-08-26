<?php

declare(strict_types=1);

namespace App\Services\AI;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

abstract class BaseAiClient
{
    protected function http(): PendingRequest
    {
        return Http::timeout(20)
            ->retry(2, 250)
            ->acceptJson();
    }

    /**
     * Tolerant JSON extractor: tries to find first {...} block and decode.
     * Returns [] if fails.
     */
    /**
     * @return array<string, mixed>
     */
    protected function tryDecode(string $text): array
    {
        $text = trim($text);
        // If proper JSON
        $decoded = json_decode($text, true);
        if (is_array($decoded)) {
            return $decoded;
        }
        // Try to extract first JSON object - use non-greedy matching
        if (preg_match('/\{[^}]*\}/s', $text, $m)) {
            $decoded = json_decode($m[0], true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return [];
    }

    /**
     * @param  array<string, mixed>  $decoded
     * @return array<string, mixed>
     */
    protected function normalize(array $decoded): array
    {
        // Enforce canonical keys, map fallbacks
        $decision = strtoupper((string) ($decoded['decision'] ?? $decoded['action'] ?? ''));
        $confidence = (float) ($decoded['confidence'] ?? $decoded['score'] ?? 0);
        $reason = $decoded['reason'] ?? $decoded['explanation'] ?? null;
        $sl = isset($decoded['stop_loss'])
            ? (float) $decoded['stop_loss']
            : (isset($decoded['stopLoss']) ? (float) $decoded['stopLoss'] : null);
        $tp = isset($decoded['take_profit'])
            ? (float) $decoded['take_profit']
            : (isset($decoded['takeProfit']) ? (float) $decoded['takeProfit'] : null);

        return compact('decision', 'confidence', 'reason', 'sl', 'tp');
    }
}
