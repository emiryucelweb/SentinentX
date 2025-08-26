<?php

declare(strict_types=1);

namespace App\Services\AI\Prompt;

use Illuminate\Support\Facades\Log;

final class PromptSecurityGuard
{
    // Comprehensive security patterns for AI prompt injection
    private const FORBIDDEN_PATTERNS = [
        // Role manipulation attempts
        '/system\s*:|role\s*:|assistant\s*:|user\s*:/i',
        '/you\s+are\s+now|act\s+as|pretend\s+to\s+be|roleplay\s+as/i',
        '/ignore\s+previous|forget\s+instructions|disregard\s+above/i',

        // Script injection attempts
        '/<script|javascript:|vbscript:|onload\s*=|onerror\s*=/i',
        '/eval\s*\(|exec\s*\(|system\s*\(|shell_exec/i',
        '/base64_decode|hex2bin|chr\s*\(|ord\s*\(/i',

        // Command injection
        '/rm\s+-rf|del\s+\/s|format\s+c:|sudo\s+|chmod\s+/i',
        '/wget\s+|curl\s+|nc\s+|netcat|telnet/i',
        '/\|\s*sh|\|\s*bash|\|\s*cmd/i',

        // Data exfiltration attempts
        '/api[_\s]*key|secret[_\s]*key|password|token/i',
        '/\b\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}\b/', // IP addresses
        '/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', // Email addresses

        // Trading manipulation attempts
        '/buy\s+all|sell\s+all|liquidate\s+all|close\s+all\s+positions/i',
        '/leverage\s*:\s*\d{3,}|leverage.*1000|maximum\s+leverage/i',
        '/emergency\s+stop|kill\s+switch|halt\s+trading/i',

        // Prompt engineering attacks
        '/\[INST\]|\[\/INST\]|<\|im_start\|>|<\|im_end\|>/i',
        '/"""|```|###\s*END|---\s*END/i',
        '/SYSTEM:|HUMAN:|AI:/i',
    ];

    // Maximum allowed prompt length (tokens * 4 for safety)
    private const MAX_PROMPT_LENGTH = 8000;

    // Suspicious patterns that warrant extra logging
    private const SUSPICIOUS_PATTERNS = [
        '/\d{10,}/', // Long numbers (could be phone/account numbers)
        '/[A-Z0-9]{20,}/', // Long uppercase strings (could be keys)
        '/\$\{|\$\(|\$\[/', // Variable substitution patterns
        '/{{|}}|\[\[|\]\]/', // Template patterns
    ];

    public function validatePrompt(string $prompt): array
    {
        $violations = [];
        $warnings = [];

        // Check prompt length
        if (strlen($prompt) > self::MAX_PROMPT_LENGTH) {
            $violations[] = 'Prompt too long: '.strlen($prompt).' chars (max: '.self::MAX_PROMPT_LENGTH.')';
        }

        // Check for forbidden patterns
        foreach (self::FORBIDDEN_PATTERNS as $pattern) {
            if (preg_match($pattern, $prompt)) {
                $violations[] = 'Security violation: Forbidden pattern detected';
                // Don't include the actual pattern in logs for security
            }
        }

        // Check for suspicious patterns (warnings, not violations)
        foreach (self::SUSPICIOUS_PATTERNS as $pattern) {
            if (preg_match($pattern, $prompt)) {
                $warnings[] = 'Suspicious pattern detected';
            }
        }

        // Additional checks
        $this->checkRepeatedPatterns($prompt, $violations);
        $this->checkEncodingAttempts($prompt, $violations);

        $isValid = empty($violations);

        // Log security events
        if (! $isValid) {
            Log::error('AI Prompt security violation', [
                'prompt_length' => strlen($prompt),
                'violation_count' => count($violations),
                'warning_count' => count($warnings),
                'prompt_hash' => hash('sha256', $prompt),
                'ip_address' => request()->ip() ?? 'unknown',
                'user_agent' => request()->userAgent() ?? 'unknown',
            ]);
        } elseif (! empty($warnings)) {
            Log::warning('AI Prompt suspicious content', [
                'prompt_length' => strlen($prompt),
                'warning_count' => count($warnings),
                'prompt_preview' => substr($prompt, 0, 50).'...',
            ]);
        }

        return [
            'is_valid' => $isValid,
            'violations' => $violations,
            'warnings' => $warnings,
            'prompt_length' => strlen($prompt),
            'security_score' => $this->calculateSecurityScore($violations, $warnings),
        ];
    }

    /**
     * Check for repeated suspicious patterns that might indicate attack attempts
     */
    private function checkRepeatedPatterns(string $prompt, array &$violations): void
    {
        // Check for excessive repetition of characters (buffer overflow attempt)
        if (preg_match('/(.)\1{100,}/', $prompt)) {
            $violations[] = 'Excessive character repetition detected';
        }

        // Check for repeated newlines or spaces (format manipulation)
        if (preg_match('/\n{10,}|\s{50,}/', $prompt)) {
            $violations[] = 'Excessive whitespace manipulation detected';
        }
    }

    /**
     * Check for encoding/obfuscation attempts
     */
    private function checkEncodingAttempts(string $prompt, array &$violations): void
    {
        // Check for base64-like patterns
        if (preg_match('/[A-Za-z0-9+\/]{50,}={0,2}/', $prompt)) {
            $violations[] = 'Potential base64 encoding detected';
        }

        // Check for hex encoding patterns
        if (preg_match('/\\\\x[0-9a-fA-F]{2}{10,}/', $prompt)) {
            $violations[] = 'Potential hex encoding detected';
        }

        // Check for unicode escape patterns
        if (preg_match('/\\\\u[0-9a-fA-F]{4}{5,}/', $prompt)) {
            $violations[] = 'Potential unicode escape sequence detected';
        }
    }

    /**
     * Calculate security score based on violations and warnings
     */
    private function calculateSecurityScore(array $violations, array $warnings): float
    {
        $score = 100.0;
        $score -= count($violations) * 25; // Each violation reduces score by 25
        $score -= count($warnings) * 5;   // Each warning reduces score by 5

        return max(0.0, $score);
    }

    /**
     * Sanitize prompt by removing potentially harmful content
     */
    public function sanitizePrompt(string $prompt): string
    {
        $sanitized = $prompt;

        // Remove potential command injection patterns
        $sanitized = preg_replace('/[|&;`$(){}\\\\]/', '', $sanitized);

        // Remove script tags and javascript
        $sanitized = preg_replace('/<script[^>]*>.*?<\/script>/is', '', $sanitized);
        $sanitized = preg_replace('/javascript:/i', '', $sanitized);

        // Trim excessive whitespace
        $sanitized = preg_replace('/\s+/', ' ', $sanitized);
        $sanitized = trim($sanitized);

        // Limit length
        if (strlen($sanitized) > self::MAX_PROMPT_LENGTH) {
            $sanitized = substr($sanitized, 0, self::MAX_PROMPT_LENGTH);
        }

        return $sanitized;
    }
}
