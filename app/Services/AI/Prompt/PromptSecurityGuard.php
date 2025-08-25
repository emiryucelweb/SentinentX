<?php

declare(strict_types=1);

namespace App\Services\AI\Prompt;

use Illuminate\Support\Facades\Log;

final class PromptSecurityGuard
{
    private const FORBIDDEN_PATTERNS = [
        '/system\s*:|role\s*:|assistant\s*:|user\s*:/i',
        '/<script|javascript:|vbscript:|onload\s*=|onerror\s*=/i',
        '/exec\s*\(|eval\s*\(|system\s*\(/i',
        '/rm\s+-rf|del\s+\/s|format\s+c:/i',
    ];

    public function validatePrompt(string $prompt): array
    {
        $violations = [];

        foreach (self::FORBIDDEN_PATTERNS as $pattern) {
            if (preg_match($pattern, $prompt)) {
                $violations[] = "Forbidden pattern detected: {$pattern}";
            }
        }

        $isValid = empty($violations);

        if (! $isValid) {
            Log::warning('AI Prompt security violation', [
                'prompt_length' => strlen($prompt),
                'violations' => $violations,
                'prompt_preview' => substr($prompt, 0, 100),
            ]);
        }

        return [
            'is_valid' => $isValid,
            'violations' => $violations,
            'prompt_length' => strlen($prompt),
        ];
    }
}
