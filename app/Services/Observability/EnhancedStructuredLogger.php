<?php

declare(strict_types=1);

namespace App\Services\Observability;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Enhanced Structured Logger - Extends basic logging with correlation IDs,
 * performance tracking, and automatic metric collection
 */
final class EnhancedStructuredLogger
{
    public function __construct(
        private readonly MetricsCollector $metricsCollector
    ) {}

    /**
     * Trading action with performance tracking and metrics
     */
    public function trading(string $action, array $context = []): void
    {
        $correlationId = $this->getOrGenerateCorrelationId();
        $timestamp = now();

        $logContext = array_merge([
            'action' => $action,
            'category' => 'trading',
            'correlation_id' => $correlationId,
            'timestamp' => $timestamp->toISOString(),
        ], $context);

        Log::channel('trading')->info('TRADING_ACTION', $logContext);

        // Collect metrics
        $this->metricsCollector->tradingMetrics(
            $context['symbol'] ?? 'UNKNOWN',
            $action,
            $context
        );
    }

    /**
     * AI consensus with provider performance tracking
     */
    public function aiConsensus(string $provider, string $action, int $confidence, array $context = []): void
    {
        $correlationId = $this->getOrGenerateCorrelationId();
        $timestamp = now();

        $logContext = array_merge([
            'provider' => $provider,
            'action' => $action,
            'confidence' => $confidence,
            'category' => 'ai_consensus',
            'correlation_id' => $correlationId,
            'timestamp' => $timestamp->toISOString(),
        ], $context);

        Log::channel('ai')->info('AI_CONSENSUS', $logContext);

        // Collect metrics
        $this->metricsCollector->aiConsensusMetrics($provider, $action, $confidence, $context);
    }

    /**
     * Risk gate with detailed context and automatic alerting
     */
    public function risk(string $gate, bool $passed, array $context = []): void
    {
        $correlationId = $this->getOrGenerateCorrelationId();
        $timestamp = now();
        $level = $passed ? 'info' : 'warning';

        $logContext = array_merge([
            'gate' => $gate,
            'passed' => $passed,
            'category' => 'risk',
            'correlation_id' => $correlationId,
            'timestamp' => $timestamp->toISOString(),
        ], $context);

        Log::channel('risk')->{$level}('RISK_GATE', $logContext);

        // Collect metrics
        $this->metricsCollector->riskMetrics($gate, $passed, $context);

        // Auto-alert on critical risk failures
        if (! $passed && ($context['severity'] ?? 'medium') === 'critical') {
            $this->criticalRiskAlert($gate, $context);
        }
    }

    /**
     * Security event with automatic threat detection
     */
    public function security(string $event, string $level = 'info', array $context = []): void
    {
        $correlationId = $this->getOrGenerateCorrelationId();
        $timestamp = now();

        $logContext = array_merge([
            'event' => $event,
            'category' => 'security',
            'severity' => $level,
            'correlation_id' => $correlationId,
            'timestamp' => $timestamp->toISOString(),
            'user_ip' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ], $context);

        $method = match ($level) {
            'critical', 'error' => 'error',
            'warning' => 'warning',
            default => 'info'
        };

        Log::channel('structured')->{$method}('SECURITY_EVENT', $logContext);

        // Auto-alert on critical security events
        if (in_array($level, ['critical', 'error'])) {
            $this->securityAlert($event, $level, $context);
        }
    }

    /**
     * Performance tracking with automatic slow operation detection
     */
    public function performance(string $operation, float $duration, array $context = []): void
    {
        $correlationId = $this->getOrGenerateCorrelationId();
        $durationMs = round($duration * 1000, 2);
        $timestamp = now();

        $logContext = array_merge([
            'operation' => $operation,
            'duration_ms' => $durationMs,
            'category' => 'performance',
            'correlation_id' => $correlationId,
            'timestamp' => $timestamp->toISOString(),
            'memory_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
        ], $context);

        // Determine log level based on duration
        $level = $this->getPerformanceLogLevel($operation, $durationMs);

        Log::channel('structured')->{$level}('PERFORMANCE_METRIC', $logContext);

        // Collect metrics
        $this->metricsCollector->performanceMetrics($operation, $durationMs, $logContext);

        // Auto-alert on slow operations
        if ($level === 'warning' || $level === 'error') {
            $this->performanceAlert($operation, $durationMs, $context);
        }
    }

    /**
     * API call tracking with error detection
     */
    public function apiCall(string $method, string $url, int $statusCode, float $duration, array $context = []): void
    {
        $correlationId = $this->getOrGenerateCorrelationId();
        $durationMs = round($duration * 1000, 2);
        $timestamp = now();

        $level = $statusCode >= 500 ? 'error' : ($statusCode >= 400 ? 'warning' : 'info');

        $logContext = array_merge([
            'method' => $method,
            'url' => $this->sanitizeUrl($url),
            'status_code' => $statusCode,
            'duration_ms' => $durationMs,
            'category' => 'api_call',
            'correlation_id' => $correlationId,
            'timestamp' => $timestamp->toISOString(),
        ], $context);

        Log::channel('structured')->{$level}('API_CALL', $logContext);

        // Auto-alert on API failures
        if ($statusCode >= 500) {
            $this->apiFailureAlert($method, $url, $statusCode, $context);
        }
    }

    /**
     * Alert dispatching with runbook references
     */
    public function alert(string $service, string $level, string $message, array $context = []): void
    {
        $correlationId = $this->getOrGenerateCorrelationId();
        $timestamp = now();

        $logContext = array_merge([
            'service' => $service,
            'level' => $level,
            'message' => $message,
            'category' => 'alert',
            'correlation_id' => $correlationId,
            'timestamp' => $timestamp->toISOString(),
            'runbook_section' => $this->getRunbookSection($service, $level),
        ], $context);

        $method = match ($level) {
            'critical', 'error' => 'error',
            'warning' => 'warning',
            default => 'info'
        };

        Log::channel('structured')->{$method}('ALERT_DISPATCHED', $logContext);
    }

    /**
     * Business event tracking for SaaS metrics
     */
    public function business(string $event, array $context = []): void
    {
        $correlationId = $this->getOrGenerateCorrelationId();
        $timestamp = now();

        $logContext = array_merge([
            'event' => $event,
            'category' => 'business',
            'correlation_id' => $correlationId,
            'timestamp' => $timestamp->toISOString(),
        ], $context);

        Log::channel('structured')->info('BUSINESS_EVENT', $logContext);

        // Collect business metrics
        if (isset($context['metric']) && isset($context['value'])) {
            $this->metricsCollector->businessMetrics(
                $context['metric'],
                (float) $context['value'],
                $context['tags'] ?? []
            );
        }
    }

    /**
     * Multi-tenant action tracking
     */
    public function tenant(string $tenantId, string $action, array $context = []): void
    {
        $correlationId = $this->getOrGenerateCorrelationId();
        $timestamp = now();

        $logContext = array_merge([
            'tenant_id' => $tenantId,
            'action' => $action,
            'category' => 'tenant',
            'correlation_id' => $correlationId,
            'timestamp' => $timestamp->toISOString(),
        ], $context);

        Log::channel('structured')->info('TENANT_ACTION', $logContext);
    }

    /**
     * Critical error with immediate escalation
     */
    public function critical(string $component, string $message, array $context = []): void
    {
        $correlationId = $this->getOrGenerateCorrelationId();
        $timestamp = now();

        $logContext = array_merge([
            'component' => $component,
            'message' => $message,
            'category' => 'critical',
            'correlation_id' => $correlationId,
            'timestamp' => $timestamp->toISOString(),
            'stack_trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10),
            'runbook_section' => $this->getRunbookSection($component, 'critical'),
        ], $context);

        Log::channel('structured')->emergency('CRITICAL_ERROR', $logContext);

        // Immediate alert dispatch
        $this->criticalAlert($component, $message, $context);
    }

    /**
     * Get or generate correlation ID for request tracking
     */
    private function getOrGenerateCorrelationId(): string
    {
        // Check if correlation ID exists in request headers
        $correlationId = request()?->header('X-Correlation-ID');

        if (! $correlationId) {
            // Generate new correlation ID
            $correlationId = Str::uuid()->toString();

            // Store in request for subsequent logs
            if (request()) {
                request()->headers->set('X-Correlation-ID', $correlationId);
            }
        }

        return $correlationId;
    }

    /**
     * Determine performance log level based on operation and duration
     */
    private function getPerformanceLogLevel(string $operation, float $durationMs): string
    {
        $thresholds = [
            'api' => ['warning' => 2000, 'error' => 5000],
            'database' => ['warning' => 1000, 'error' => 3000],
            'ai' => ['warning' => 10000, 'error' => 30000],
            'trading' => ['warning' => 5000, 'error' => 15000],
            'default' => ['warning' => 3000, 'error' => 10000],
        ];

        $operationType = 'default';
        foreach (['api', 'database', 'ai', 'trading'] as $type) {
            if (str_contains(strtolower($operation), $type)) {
                $operationType = $type;
                break;
            }
        }

        $threshold = $thresholds[$operationType];

        if ($durationMs >= $threshold['error']) {
            return 'error';
        } elseif ($durationMs >= $threshold['warning']) {
            return 'warning';
        }

        return 'info';
    }

    /**
     * Sanitize URL for logging (remove sensitive parameters)
     */
    private function sanitizeUrl(string $url): string
    {
        $sensitiveParams = ['api_key', 'secret', 'password', 'token', 'auth'];

        foreach ($sensitiveParams as $param) {
            $url = preg_replace("/([?&]){$param}=[^&]*/i", "$1{$param}=***", $url) ?? $url;
        }

        return $url;
    }

    /**
     * Get runbook section reference for alerts
     */
    private function getRunbookSection(string $service, string $level): string
    {
        $runbookMap = [
            'trading' => [
                'critical' => 'section-trading-critical',
                'error' => 'section-trading-errors',
                'warning' => 'section-trading-monitoring',
            ],
            'ai' => [
                'critical' => 'section-ai-critical',
                'error' => 'section-ai-errors',
                'warning' => 'section-ai-monitoring',
            ],
            'exchange' => [
                'critical' => 'section-exchange-critical',
                'error' => 'section-exchange-errors',
                'warning' => 'section-exchange-monitoring',
            ],
            'risk' => [
                'critical' => 'section-risk-critical',
                'error' => 'section-risk-errors',
                'warning' => 'section-risk-monitoring',
            ],
            'security' => [
                'critical' => 'section-security-critical',
                'error' => 'section-security-errors',
                'warning' => 'section-security-monitoring',
            ],
        ];

        return $runbookMap[$service][$level] ?? 'section-general-troubleshooting';
    }

    /**
     * Critical risk alert
     */
    private function criticalRiskAlert(string $gate, array $context): void
    {
        // This would integrate with AlertDispatcher
        Log::emergency("Critical risk gate failed: {$gate}", $context);
    }

    /**
     * Security alert
     */
    private function securityAlert(string $event, string $level, array $context): void
    {
        // This would integrate with AlertDispatcher
        Log::error("Security event: {$event} (Level: {$level})", $context);
    }

    /**
     * Performance alert
     */
    private function performanceAlert(string $operation, float $durationMs, array $context): void
    {
        // This would integrate with AlertDispatcher
        Log::warning("Slow operation detected: {$operation} took {$durationMs}ms", $context);
    }

    /**
     * API failure alert
     */
    private function apiFailureAlert(string $method, string $url, int $statusCode, array $context): void
    {
        // This would integrate with AlertDispatcher
        Log::error("API failure: {$method} {$url} returned {$statusCode}", $context);
    }

    /**
     * Critical alert with immediate escalation
     */
    private function criticalAlert(string $component, string $message, array $context): void
    {
        // This would integrate with AlertDispatcher for immediate notification
        Log::emergency("CRITICAL ALERT: {$component} - {$message}", $context);
    }
}
