<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Security\Contracts\Allowlist;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IpAllowlistMiddleware
{
    public function __construct(private readonly Allowlist $allowlist) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! config('security.allowlist.enabled', true)) {
            return $next($request);
        }

        $clientIp = $request->ip();

        if (! $this->allowlist->isAllowed($clientIp)) {
            return response()->json(['error' => 'IP not allowed'], 403);
        }

        return $next($request);
    }
}
