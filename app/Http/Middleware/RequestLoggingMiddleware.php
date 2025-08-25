<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class RequestLoggingMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);
        $requestId = uniqid('req_', true);

        // Log request
        Log::channel('json')->info('HTTP Request Started', [
            'request_id' => $requestId,
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'user_id' => $this->getUserId(),
            'headers' => $request->headers->all(),
            'query_params' => $request->query->all(),
            'body_params' => $request->post(),
        ]);

        // Add request ID to request for tracking
        $request->attributes->set('request_id', $requestId);

        $response = $next($request);

        // Calculate duration
        $duration = microtime(true) - $startTime;

        // Log response
        Log::channel('json')->info('HTTP Request Completed', [
            'request_id' => $requestId,
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'status_code' => $response->getStatusCode(),
            'duration_ms' => round($duration * 1000, 2),
            'memory_usage' => memory_get_usage(true),
            'response_size' => strlen($response->getContent()),
        ]);

        return $response;
    }

    /**
     * Get user ID safely
     */
    protected function getUserId(): ?int
    {
        try {
            $guard = auth();
            if ($guard->check()) {
                $userId = $guard->id();

                return is_int($userId) ? $userId : null;
            }
        } catch (\Exception $e) {
            // Fallback if auth is not available
        }

        return null;
    }
}
