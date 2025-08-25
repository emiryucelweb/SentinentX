<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Security Headers Middleware
 * Adds security headers to all HTTP responses
 */
class SecurityHeadersMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Force HTTPS in production
        if (app()->environment('production')) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
        }

        // Content Security Policy
        $csp = implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net",
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com",
            "font-src 'self' https://fonts.gstatic.com",
            "img-src 'self' data: https:",
            "connect-src 'self' https://api.bybit.com wss://stream.bybit.com",
            "frame-ancestors 'none'",
            "base-uri 'self'",
            "object-src 'none'",
        ]);
        $response->headers->set('Content-Security-Policy', $csp);

        // XSS Protection
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        // Content Type Options
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // Frame Options
        $response->headers->set('X-Frame-Options', 'DENY');

        // Referrer Policy
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Feature Policy
        $response->headers->set('Permissions-Policy',
            'camera=(), microphone=(), geolocation=(), payment=(), usb=()');

        // Remove server information
        $response->headers->remove('Server');
        $response->headers->remove('X-Powered-By');

        // API specific headers
        if ($request->is('api/*')) {
            $response->headers->set('X-API-Version', '2.1.0');
            $response->headers->set('X-RateLimit-Limit', '1000');
            $response->headers->set('X-Request-ID', $request->header('X-Request-ID', uniqid()));
        }

        return $response;
    }
}
