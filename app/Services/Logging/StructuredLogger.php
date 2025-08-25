<?php

namespace App\Services\Logging;

use Illuminate\Support\Facades\Log;

class StructuredLogger
{
    public static function trading(string $action, array $context = []): void
    {
        Log::channel('structured')->info('TRADING_ACTION', array_merge([
            'action' => $action,
            'category' => 'trading',
        ], $context));
    }

    public static function consensus(string $provider, string $action, int $confidence, array $context = []): void
    {
        Log::channel('structured')->info('AI_CONSENSUS', array_merge([
            'provider' => $provider,
            'action' => $action,
            'confidence' => $confidence,
            'category' => 'consensus',
        ], $context));
    }

    public static function risk(string $gate, bool $passed, array $context = []): void
    {
        Log::channel('structured')->info('RISK_GATE', array_merge([
            'gate' => $gate,
            'passed' => $passed,
            'category' => 'risk',
        ], $context));
    }

    public static function security(string $event, string $level = 'info', array $context = []): void
    {
        $method = $level === 'warning' ? 'warning' : ($level === 'error' ? 'error' : 'info');

        Log::channel('structured')->{$method}('SECURITY_EVENT', array_merge([
            'event' => $event,
            'category' => 'security',
        ], $context));
    }

    public static function performance(string $operation, float $duration, array $context = []): void
    {
        Log::channel('structured')->info('PERFORMANCE_METRIC', array_merge([
            'operation' => $operation,
            'duration_ms' => round($duration * 1000, 2),
            'category' => 'performance',
        ], $context));
    }

    public static function api(string $method, string $url, int $statusCode, float $duration, array $context = []): void
    {
        $level = $statusCode >= 400 ? 'warning' : 'info';

        Log::channel('structured')->{$level}('API_CALL', array_merge([
            'method' => $method,
            'url' => $url,
            'status_code' => $statusCode,
            'duration_ms' => round($duration * 1000, 2),
            'category' => 'api',
        ], $context));
    }

    public static function alert(string $service, string $level, string $message, array $context = []): void
    {
        $method = match ($level) {
            'critical', 'error' => 'error',
            'warning' => 'warning',
            default => 'info'
        };

        Log::channel('structured')->{$method}('ALERT_DISPATCHED', array_merge([
            'service' => $service,
            'level' => $level,
            'message' => $message,
            'category' => 'alert',
        ], $context));
    }

    public static function tenant(string $tenantId, string $action, array $context = []): void
    {
        Log::channel('structured')->info('TENANT_ACTION', array_merge([
            'tenant_id' => $tenantId,
            'action' => $action,
            'category' => 'tenant',
        ], $context));
    }
}
