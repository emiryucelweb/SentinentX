<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class CorrelationIdMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Generate or extract correlation ID
        $correlationId = $request->headers->get('X-Request-Id')
            ?? $request->headers->get('X-Correlation-Id')
            ?? (string) Str::uuid();

        // Store in request for easy access
        $request->attributes->set('correlation_id', $correlationId);

        // Add to Log context for all subsequent log calls
        Log::withContext([
            'cid' => $correlationId,
            'tenant' => $request->attributes->get('tenant_id'),
            'user' => $request->user()?->id,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        $response = $next($request);

        // Add correlation ID to response headers
        $response->headers->set('X-Correlation-Id', $correlationId);

        return $response;
    }
}
