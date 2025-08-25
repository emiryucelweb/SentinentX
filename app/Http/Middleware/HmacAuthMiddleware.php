<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * HMAC Authentication Middleware for Admin API
 * Implements secure HMAC-SHA256 signature verification
 */
class HmacAuthMiddleware
{
    public function handle(Request $request, Closure $next): SymfonyResponse
    {

        $signature = $request->header('X-Signature');
        $timestamp = $request->header('X-Timestamp');

        if (! $signature || ! $timestamp) {
            return response()->json([
                'error' => 'Missing required headers: X-Signature, X-Timestamp',
            ], 401);
        }

        // Check timestamp to prevent replay attacks (5 minute window)
        $now = time();
        $requestTime = (int) $timestamp;
        if (abs($now - $requestTime) > 300) {
            return response()->json([
                'error' => 'Request timestamp expired',
            ], 401);
        }

        // Nonce replay-cache check (Redis TTL=300s)
        $nonce = $request->header('X-Nonce');
        if (! $nonce) {
            return response()->json([
                'error' => 'Missing X-Nonce header',
            ], 401);
        }

        $nonceKey = "hmac_nonce:{$nonce}";
        if (\Illuminate\Support\Facades\Redis::exists($nonceKey)) {
            return response()->json([
                'error' => 'Nonce already used (replay attack detected)',
            ], 401);
        }

        // Store nonce in Redis with TTL=300s
        \Illuminate\Support\Facades\Redis::setex($nonceKey, 300, $timestamp);

        Log::info('HMAC nonce stored in replay cache', [
            'nonce' => $nonce,
            'cache_key' => $nonceKey,
            'ttl_seconds' => 300,
            'timestamp' => $timestamp,
        ]);

        // Canonical payload construction
        $method = $request->method();
        $path = $request->getPathInfo();
        $query = $request->getQueryString();
        $sortedQuery = $query ? $this->canonicalizeQuery($query) : '';
        $body = $request->getContent();
        $contentHash = hash('sha256', $body);

        $payload = implode("\n", [
            $method,
            $path,
            $sortedQuery,
            $timestamp,
            $nonce,
            $contentHash,
        ]);

        // Get HMAC secret from config
        $secret = config('security.hmac_secret');
        if (! $secret) {
            return response()->json([
                'error' => 'HMAC secret not configured',
            ], 500);
        }

        // Calculate expected signature
        $expectedSignature = hash_hmac('sha256', $payload, $secret);

        // Verify signature using timing-safe comparison
        if (! hash_equals($expectedSignature, $signature)) {
            return response()->json([
                'error' => 'Invalid signature',
            ], 401);
        }

        return $next($request);
    }

    /**
     * Canonicalize query string for consistent signature
     */
    private function canonicalizeQuery(string $query): string
    {
        parse_str($query, $params);
        ksort($params);

        return http_build_query($params, '', '&', PHP_QUERY_RFC3986);
    }
}
