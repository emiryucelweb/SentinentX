<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\SaaS\TenantManager;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TenantContextMiddleware
{
    public function __construct(private readonly TenantManager $tenantManager) {}

    public function handle(Request $request, Closure $next): Response
    {
        $tenantId = $this->extractTenantId($request);

        if ($tenantId) {
            $this->tenantManager->setCurrentTenant($tenantId);
        }

        $response = $next($request);

        // Reset tenant context after request
        $this->tenantManager->resetTenant();

        return $response;
    }

    private function extractTenantId(Request $request): ?int
    {
        // Try multiple sources for tenant identification

        // 1. URL subdomain (tenant.sentx.com)
        $host = $request->getHost();
        if (preg_match('/^(\w+)\.sentx\./', $host, $matches)) {
            return $this->resolveTenantBySlug($matches[1]);
        }

        // 2. API key prefix (tenant_id:api_key)
        $apiKey = $request->header('X-API-Key');
        if ($apiKey && str_contains($apiKey, ':')) {
            [$tenantId, $key] = explode(':', $apiKey, 2);
            if (is_numeric($tenantId)) {
                return (int) $tenantId;
            }
        }

        // 3. JWT claims
        $user = $request->user();
        if ($user && isset($user->tenant_id)) {
            return $user->tenant_id;
        }

        // 4. Request header
        $tenantHeader = $request->header('X-Tenant-ID');
        if ($tenantHeader && is_numeric($tenantHeader)) {
            return (int) $tenantHeader;
        }

        return null;
    }

    private function resolveTenantBySlug(string $slug): ?int
    {
        // Cache this lookup in production
        $tenant = \App\Models\Tenant::where('slug', $slug)->first();

        return $tenant?->id;
    }
}
