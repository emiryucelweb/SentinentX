<?php

declare(strict_types=1);

namespace App\Services\Reliability;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Circuit Breaker implementation for external API calls
 *
 * States:
 * - CLOSED: Normal operation
 * - OPEN: Failing, block all requests
 * - HALF_OPEN: Testing if service recovered
 */
class CircuitBreakerService
{
    private const STATE_CLOSED = 'closed';

    private const STATE_OPEN = 'open';

    private const STATE_HALF_OPEN = 'half_open';

    private string $serviceName;

    private int $failureThreshold;

    private int $recoveryTimeout;

    private int $timeout;

    public function __construct(
        string $serviceName,
        int $failureThreshold = 5,
        int $recoveryTimeout = 60,
        int $timeout = 30
    ) {
        $this->serviceName = $serviceName;
        $this->failureThreshold = $failureThreshold;
        $this->recoveryTimeout = $recoveryTimeout;
        $this->timeout = $timeout;
    }

    /**
     * Execute a callable with circuit breaker protection
     */
    public function call(callable $callback): mixed
    {
        $state = $this->getState();

        // If circuit is open, fail fast
        if ($state === self::STATE_OPEN) {
            if ($this->shouldAttemptRecovery()) {
                $this->setState(self::STATE_HALF_OPEN);
                Log::info('Circuit breaker attempting recovery', [
                    'service' => $this->serviceName,
                    'state' => 'half_open',
                ]);
            } else {
                throw new RuntimeException("Circuit breaker OPEN for service: {$this->serviceName}");
            }
        }

        try {
            $startTime = microtime(true);
            $result = $callback();
            $duration = (microtime(true) - $startTime) * 1000; // Convert to milliseconds

            // Success - reset failure count or close circuit
            if ($state === self::STATE_HALF_OPEN) {
                $this->setState(self::STATE_CLOSED);
                $this->resetFailureCount();
                Log::info('Circuit breaker recovered', [
                    'service' => $this->serviceName,
                    'state' => 'closed',
                    'duration_ms' => round($duration, 2),
                ]);
            } else {
                $this->resetFailureCount();
            }

            $this->recordSuccess($duration);

            return $result;

        } catch (\Exception $e) {
            $this->recordFailure($e);

            $failureCount = $this->incrementFailureCount();

            // Open circuit if threshold exceeded
            if ($failureCount >= $this->failureThreshold && $state !== self::STATE_OPEN) {
                $this->setState(self::STATE_OPEN);
                $this->setRecoveryTime();

                Log::error('Circuit breaker opened', [
                    'service' => $this->serviceName,
                    'failure_count' => $failureCount,
                    'threshold' => $this->failureThreshold,
                    'error' => $e->getMessage(),
                ]);
            }

            throw $e;
        }
    }

    /**
     * Get current circuit breaker state
     */
    public function getState(): string
    {
        return Cache::get($this->getStateKey(), self::STATE_CLOSED);
    }

    /**
     * Get circuit breaker status for monitoring
     */
    public function getStatus(): array
    {
        return [
            'service' => $this->serviceName,
            'state' => $this->getState(),
            'failure_count' => $this->getFailureCount(),
            'failure_threshold' => $this->failureThreshold,
            'recovery_timeout' => $this->recoveryTimeout,
            'last_failure_time' => Cache::get($this->getLastFailureKey()),
            'recovery_time' => Cache::get($this->getRecoveryTimeKey()),
        ];
    }

    /**
     * Force circuit breaker to specific state (for testing/emergency)
     */
    public function forceState(string $state): void
    {
        $this->setState($state);

        Log::warning('Circuit breaker state forced', [
            'service' => $this->serviceName,
            'forced_state' => $state,
        ]);
    }

    /**
     * Reset circuit breaker to initial state
     */
    public function reset(): void
    {
        Cache::forget($this->getStateKey());
        Cache::forget($this->getFailureCountKey());
        Cache::forget($this->getRecoveryTimeKey());
        Cache::forget($this->getLastFailureKey());

        Log::info('Circuit breaker reset', [
            'service' => $this->serviceName,
        ]);
    }

    private function setState(string $state): void
    {
        Cache::put($this->getStateKey(), $state, now()->addHours(24));
    }

    private function getFailureCount(): int
    {
        return Cache::get($this->getFailureCountKey(), 0);
    }

    private function incrementFailureCount(): int
    {
        $key = $this->getFailureCountKey();
        $count = Cache::get($key, 0) + 1;
        Cache::put($key, $count, now()->addHours(1));

        return $count;
    }

    private function resetFailureCount(): void
    {
        Cache::forget($this->getFailureCountKey());
    }

    private function shouldAttemptRecovery(): bool
    {
        $recoveryTime = Cache::get($this->getRecoveryTimeKey());

        return $recoveryTime && now()->timestamp >= $recoveryTime;
    }

    private function setRecoveryTime(): void
    {
        $recoveryTime = now()->addSeconds($this->recoveryTimeout)->timestamp;
        Cache::put($this->getRecoveryTimeKey(), $recoveryTime, now()->addHours(24));
    }

    private function recordFailure(\Exception $e): void
    {
        Cache::put($this->getLastFailureKey(), [
            'time' => now()->toISOString(),
            'error' => $e->getMessage(),
            'type' => get_class($e),
        ], now()->addHours(24));
    }

    private function recordSuccess(float $duration): void
    {
        // Could be extended to track success metrics
        Log::debug('Circuit breaker success', [
            'service' => $this->serviceName,
            'duration_ms' => round($duration, 2),
        ]);
    }

    private function getStateKey(): string
    {
        return "circuit_breaker:state:{$this->serviceName}";
    }

    private function getFailureCountKey(): string
    {
        return "circuit_breaker:failures:{$this->serviceName}";
    }

    private function getRecoveryTimeKey(): string
    {
        return "circuit_breaker:recovery:{$this->serviceName}";
    }

    private function getLastFailureKey(): string
    {
        return "circuit_breaker:last_failure:{$this->serviceName}";
    }
}
