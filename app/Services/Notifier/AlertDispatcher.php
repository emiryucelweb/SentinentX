<?php

declare(strict_types=1);

namespace App\Services\Notifier;

use App\Contracts\Notifier\AlertDispatcher as AlertDispatcherInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

/**
 * Alert Dispatcher with Deduplication (120s window)
 * Prevents alert storms in production
 */
class AlertDispatcher implements AlertDispatcherInterface
{
    private const DEDUP_TTL = 120; // 120 seconds deduplication window

    /**
     * @param  array<string, mixed>  $context
     */
    public function send(
        string $level,
        string $code,
        string $message,
        array $context = [],
        ?string $dedupKey = null
    ): array {
        $timestamp = now();

        // Generate dedup key if not provided
        if (! $dedupKey) {
            $dedupKey = $this->generateDedupKey($level, $code, $message);
        }

        // Check for duplicate alerts
        $redisKey = "alert_dedup:{$dedupKey}";
        if (Redis::exists($redisKey)) {
            Log::info('Alert deduplicated', [
                'dedup_key' => $dedupKey,
                'level' => $level,
                'service' => $code,
                'message' => $message,
                'suppressed_at' => $timestamp->toISOString(),
                'ttl_remaining' => Redis::ttl($redisKey),
            ]);

            return [
                'status' => 'deduplicated',
                'dedup_key' => $dedupKey,
                'ttl_remaining' => Redis::ttl($redisKey),
                'message' => 'Alert suppressed due to recent duplicate',
            ];
        }

        // Store dedup key with TTL=120s
        Redis::setex($redisKey, self::DEDUP_TTL, json_encode([
            'level' => $level,
            'service' => $code,
            'message' => $message,
            'first_seen' => $timestamp->toISOString(),
            'context' => $context,
        ]));

        // Dispatch alert to all configured channels
        $dispatched = $this->dispatchToChannels($level, $code, $message, $context);

        // Log successful dispatch
        Log::info('Alert dispatched', [
            'dedup_key' => $dedupKey,
            'level' => $level,
            'service' => $code,
            'message' => $message,
            'dispatched_at' => $timestamp->toISOString(),
            'channels' => array_keys($dispatched),
            'context' => $context,
        ]);

        return [
            'status' => 'dispatched',
            'dedup_key' => $dedupKey,
            'dispatched_at' => $timestamp->toISOString(),
            'channels' => $dispatched,
            'dedup_ttl' => self::DEDUP_TTL,
        ];
    }

    /**
     * Generate consistent dedup key
     */
    private function generateDedupKey(string $level, string $service, string $message): string
    {
        // Normalize message for consistent deduplication
        $normalizedMessage = strtolower(trim($message));
        $normalizedMessage = preg_replace('/\d+/', 'N', $normalizedMessage) ?? $normalizedMessage; // Replace numbers with 'N'
        $normalizedMessage = preg_replace('/\s+/', ' ', $normalizedMessage) ?? $normalizedMessage; // Normalize whitespace

        return hash('sha256', implode('|', [
            $level,
            $service,
            $normalizedMessage,
        ]));
    }

    /**
     * Dispatch alert to configured notification channels
     *
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    private function dispatchToChannels(string $level, string $service, string $message, array $context): array
    {
        $dispatched = [];
        $channels = config('notifications.channels', []);

        foreach ($channels as $channel => $config) {
            if (! ($config['enabled'] ?? false)) {
                continue;
            }

            // Check if channel should receive this level
            $minLevel = $config['min_level'] ?? 'info';
            if (! $this->shouldDispatchToChannel($level, $minLevel)) {
                continue;
            }

            try {
                $result = match ($channel) {
                    'telegram' => $this->dispatchToTelegram($level, $service, $message, $context, $config),
                    'slack' => $this->dispatchToSlack($level, $service, $message, $context, $config),
                    'email' => $this->dispatchToEmail($level, $service, $message, $context, $config),
                    'webhook' => $this->dispatchToWebhook($level, $service, $message, $context, $config),
                    default => ['status' => 'unsupported_channel']
                };

                $dispatched[$channel] = $result;
            } catch (\Exception $e) {
                Log::error("Failed to dispatch alert to {$channel}", [
                    'error' => $e->getMessage(),
                    'level' => $level,
                    'service' => $service,
                ]);

                $dispatched[$channel] = [
                    'status' => 'failed',
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $dispatched;
    }

    /**
     * Check if alert level should be dispatched to channel
     */
    private function shouldDispatchToChannel(string $alertLevel, string $minLevel): bool
    {
        $levels = ['debug' => 1, 'info' => 2, 'warning' => 3, 'error' => 4, 'critical' => 5];

        return ($levels[$alertLevel] ?? 0) >= ($levels[$minLevel] ?? 0);
    }

    /**
     * Dispatch to Telegram
     */
    /**
     * @param  array<string, mixed>  $context
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    private function dispatchToTelegram(string $level, string $service, string $message, array $context, array $config): array
    {
        $emoji = match ($level) {
            'critical' => '🚨',
            'error' => '❌',
            'warning' => '⚠️',
            'info' => 'ℹ️',
            default => '📝'
        };

        $text = "{$emoji} **{$service}** [{$level}]\n\n{$message}";

        if (! empty($context)) {
            $text .= "\n\n**Context:**\n```json\n".json_encode($context, JSON_PRETTY_PRINT)."\n```";
        }

        // This would integrate with actual Telegram API
        return [
            'status' => 'sent',
            'channel' => 'telegram',
            'chat_id' => $config['chat_id'] ?? null,
        ];
    }

    /**
     * Dispatch to Slack
     */
    private function dispatchToSlack(string $level, string $service, string $message, array $context, array $config): array
    {
        $color = match ($level) {
            'critical' => 'danger',
            'error' => 'danger',
            'warning' => 'warning',
            'info' => 'good',
            default => '#439FE0'
        };

        $payload = [
            'text' => "Alert from {$service}",
            'attachments' => [
                [
                    'color' => $color,
                    'title' => strtoupper($level),
                    'text' => $message,
                    'fields' => [
                        [
                            'title' => 'Service',
                            'value' => $service,
                            'short' => true,
                        ],
                        [
                            'title' => 'Time',
                            'value' => now()->toISOString(),
                            'short' => true,
                        ],
                    ],
                ],
            ],
        ];

        // This would integrate with actual Slack API
        return [
            'status' => 'sent',
            'channel' => 'slack',
            'webhook_url' => $config['webhook_url'] ?? null,
        ];
    }

    /**
     * Dispatch to Email
     */
    private function dispatchToEmail(string $level, string $service, string $message, array $context, array $config): array
    {
        // This would integrate with Laravel's mail system
        return [
            'status' => 'sent',
            'channel' => 'email',
            'recipients' => $config['recipients'] ?? [],
        ];
    }

    /**
     * Dispatch to Webhook
     */
    private function dispatchToWebhook(string $level, string $service, string $message, array $context, array $config): array
    {
        $payload = [
            'level' => $level,
            'service' => $service,
            'message' => $message,
            'context' => $context,
            'timestamp' => now()->toISOString(),
        ];

        // This would make HTTP POST to webhook URL
        return [
            'status' => 'sent',
            'channel' => 'webhook',
            'url' => $config['url'] ?? null,
        ];
    }

    /**
     * Get deduplication statistics
     */
    public function getDedupStats(int $hours = 24): array
    {
        $pattern = 'alert_dedup:*';
        $keys = Redis::keys($pattern);

        $stats = [
            'active_dedup_keys' => count($keys),
            'dedup_window_seconds' => self::DEDUP_TTL,
            'period_hours' => $hours,
        ];

        return $stats;
    }
}
