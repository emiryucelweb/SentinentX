<?php

namespace App\Services\Logger;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class StructuredLogService
{
    /**
     * Log a structured message with context
     */
    /**
     * @param  array<string, mixed>  $context
     */
    public function log(string $channel, string $level, string $message, array $context = []): void
    {
        $structuredContext = $this->structureContext($context);

        Log::channel($channel)->log($level, $message, $structuredContext);
    }

    /**
     * Log trading related events
     */
    /**
     * @param  array<string, mixed>  $context
     */
    public function trading(string $message, array $context = []): void
    {
        $this->log('trading', 'info', $message, $context);
    }

    /**
     * Log AI related events
     */
    /**
     * @param  array<string, mixed>  $context
     */
    public function ai(string $message, array $context = []): void
    {
        $this->log('ai', 'info', $message, $context);
    }

    /**
     * Log risk related events
     */
    /**
     * @param  array<string, mixed>  $context
     */
    public function risk(string $message, array $context = []): void
    {
        $this->log('risk', 'warning', $message, $context);
    }

    /**
     * Log lab related events
     */
    /**
     * @param  array<string, mixed>  $context
     */
    public function lab(string $message, array $context = []): void
    {
        $this->log('lab', 'info', $message, $context);
    }

    /**
     * Log with error level
     *
     * @param  array<string, mixed>  $context
     */
    public function error(string $channel, string $message, array $context = []): void
    {
        $this->log($channel, 'error', $message, $context);
    }

    /**
     * Log with warning level
     *
     * @param  array<string, mixed>  $context
     */
    public function warning(string $channel, string $message, array $context = []): void
    {
        $this->log($channel, 'warning', $message, $context);
    }

    /**
     * Log with info level
     */
    public function info(string $channel, string $message, array $context = []): void
    {
        $this->log($channel, 'info', $message, $context);
    }

    /**
     * Log with debug level
     */
    public function debug(string $channel, string $message, array $context = []): void
    {
        $this->log($channel, 'debug', $message, $context);
    }

    /**
     * Structure the context for better JSON logging
     *
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    protected function structureContext(array $context): array
    {
        $structured = [
            'timestamp' => now()->toISOString(),
            'request_id' => $this->getRequestId(),
            'user_id' => $this->getUserId(),
            'session_id' => $this->getSessionId(),
            'ip_address' => $this->getIpAddress(),
            'user_agent' => $this->getUserAgent(),
        ];

        // Add custom context
        foreach ($context as $key => $value) {
            if (is_array($value) || is_object($value)) {
                $structured[$key] = json_decode(json_encode($value), true);
            } else {
                $structured[$key] = $value;
            }
        }

        return $structured;
    }

    /**
     * Get request ID safely
     */
    protected function getRequestId(): string
    {
        try {
            if (app()->bound('request') && request()) {
                return request()->attributes->get('request_id', Str::uuid());
            }
        } catch (\Exception $e) {
            // Fallback if request is not available
        }

        return Str::uuid();
    }

    /**
     * Get user ID safely
     */
    protected function getUserId(): ?int
    {
        try {
            if (app()->bound('auth') && auth()->check()) {
                return auth()->id();
            }
        } catch (\Exception $e) {
            // Fallback if auth is not available
        }

        return null;
    }

    /**
     * Get session ID safely
     */
    protected function getSessionId(): ?string
    {
        try {
            if (app()->bound('session') && session()) {
                return session()->getId();
            }
        } catch (\Exception $e) {
            // Fallback if session is not available
        }

        return null;
    }

    /**
     * Get IP address safely
     */
    protected function getIpAddress(): ?string
    {
        try {
            if (app()->bound('request') && request()) {
                return request()->ip();
            }
        } catch (\Exception $e) {
            // Fallback if request is not available
        }

        return null;
    }

    /**
     * Get user agent safely
     */
    protected function getUserAgent(): ?string
    {
        try {
            if (app()->bound('request') && request()) {
                return request()->userAgent();
            }
        } catch (\Exception $e) {
            // Fallback if request is not available
        }

        return null;
    }

    /**
     * Log performance metrics
     */
    public function performance(string $operation, float $duration, array $metrics = []): void
    {
        $context = array_merge($metrics, [
            'operation' => $operation,
            'duration_ms' => round($duration * 1000, 2),
            'memory_usage' => memory_get_usage(true),
            'peak_memory' => memory_get_peak_usage(true),
        ]);

        $this->log('json', 'info', "Performance: {$operation}", $context);
    }

    /**
     * Log business events
     */
    public function businessEvent(string $event, string $entity, $entityId, array $data = []): void
    {
        $context = array_merge($data, [
            'event_type' => $event,
            'entity_type' => $entity,
            'entity_id' => $entityId,
        ]);

        $this->log('json', 'info', "Business Event: {$event}", $context);
    }
}
