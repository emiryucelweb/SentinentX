<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Security\Contracts\HmacSigner;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HmacAuthenticationMiddleware
{
    public function __construct(private readonly HmacSigner $signer) {}

    public function handle(Request $request, Closure $next): Response
    {
        $signature = $request->header('X-Signature');
        $timestamp = $request->header('X-Timestamp');

        if (! $signature || ! $timestamp) {
            return response()->json(['error' => 'Missing signature or timestamp'], 401);
        }

        $payload = $request->getContent();

        if (! $this->signer->verify($payload, $timestamp, $signature)) {
            return response()->json(['error' => 'Invalid signature'], 401);
        }

        return $next($request);
    }
}
