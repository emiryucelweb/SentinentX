<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * SaaS API Rate Limiting Middleware
 * Tenant-aware rate limiting with plan-based limits
 */
class ApiRateLimitMiddleware
{
    public function __construct(
        private readonly RateLimiter $limiter
    ) {}

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $limits = '60,1'): Response
    {
        $user = Auth::user();
        
        if (!$user || !$user->tenant_id) {
            return $this->buildResponse('Rate limit requires authentication', 401);
        }

        $subscription = $user->activeSubscription();
        if (!$subscription) {
            return $this->buildResponse('Active subscription required', 402);
        }

        // Parse limits: "requests,minutes" or use plan-based limits
        [$requests, $minutes] = $this->parseLimits($limits, $subscription->plan);

        // Create unique key for tenant-based rate limiting
        $key = $this->buildRateLimitKey($user->tenant_id, $request);

        // Check rate limit
        if ($this->limiter->tooManyAttempts($key, $requests)) {
            return $this->buildRateLimitExceededResponse($key, $requests, $minutes);
        }

        // Increment attempts
        $this->limiter->hit($key, $minutes * 60);

        $response = $next($request);

        // Add rate limit headers
        return $this->addRateLimitHeaders($response, $key, $requests, $minutes);
    }

    /**
     * Parse rate limit configuration
     */
    private function parseLimits(string $limits, string $plan): array
    {
        // Check if plan has specific API rate limits
        $planLimits = config("billing.plans.{$plan}.api_rate_limits");
        
        if ($planLimits) {
            return [$planLimits['requests'], $planLimits['minutes']];
        }

        // Fallback to middleware parameter
        $parts = explode(',', $limits);
        $requests = (int) ($parts[0] ?? 60);
        $minutes = (int) ($parts[1] ?? 1);

        return [$requests, $minutes];
    }

    /**
     * Build rate limit key for tenant isolation
     */
    private function buildRateLimitKey(string $tenantId, Request $request): string
    {
        $endpoint = $request->route()?->getName() ?? $request->path();
        $method = $request->method();
        
        return "rate_limit:tenant:{$tenantId}:{$method}:{$endpoint}";
    }

    /**
     * Build rate limit exceeded response
     */
    private function buildRateLimitExceededResponse(string $key, int $requests, int $minutes): Response
    {
        $retryAfter = $this->limiter->availableIn($key);
        
        Log::warning('API rate limit exceeded', [
            'key' => $key,
            'limit' => $requests,
            'window_minutes' => $minutes,
            'retry_after' => $retryAfter,
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        return $this->buildResponse(
            'Rate limit exceeded. Too many requests.',
            429,
            [
                'X-RateLimit-Limit' => (string) $requests,
                'X-RateLimit-Remaining' => '0',
                'X-RateLimit-Reset' => (string) (now()->timestamp + $retryAfter),
                'Retry-After' => (string) $retryAfter,
            ],
            [
                'error' => 'Rate limit exceeded',
                'code' => 'RATE_LIMIT_EXCEEDED',
                'details' => [
                    'limit' => $requests,
                    'window_minutes' => $minutes,
                    'retry_after_seconds' => $retryAfter,
                    'reset_time' => now()->addSeconds($retryAfter)->toISOString(),
                ]
            ]
        );
    }

    /**
     * Add rate limit headers to response
     */
    private function addRateLimitHeaders(Response $response, string $key, int $requests, int $minutes): Response
    {
        $attempts = $this->limiter->attempts($key);
        $remaining = max(0, $requests - $attempts);
        $resetTime = now()->timestamp + $this->limiter->availableIn($key);

        $response->headers->set('X-RateLimit-Limit', (string) $requests);
        $response->headers->set('X-RateLimit-Remaining', (string) $remaining);
        $response->headers->set('X-RateLimit-Reset', (string) $resetTime);
        $response->headers->set('X-RateLimit-Window', (string) $minutes);

        return $response;
    }

    /**
     * Build JSON response
     */
    private function buildResponse(string $message, int $status, array $headers = [], array $data = []): Response
    {
        $responseData = empty($data) ? ['message' => $message] : $data;
        
        return new Response(
            json_encode($responseData),
            $status,
            array_merge(['Content-Type' => 'application/json'], $headers)
        );
    }
}
