<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * Multi-tenant middleware
 * Request'leri tenant context'ine göre yönlendirir ve isolate eder
 */
class TenantMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = $this->resolveTenant($request);

        if (! $tenant) {
            return response()->json([
                'error' => 'Invalid tenant',
                'message' => 'Tenant could not be resolved from request',
            ], 400);
        }

        // Tenant context'ini set et
        $this->setTenantContext($tenant);

        // Request'e tenant bilgisini ekle
        $request->attributes->set('tenant', $tenant);

        $response = $next($request);

        // Response'a tenant headers ekle
        $response->headers->set('X-Tenant-ID', $tenant->id);
        $response->headers->set('X-Tenant-Domain', $tenant->domain);

        return $response;
    }

    /**
     * Request'ten tenant'ı resolve eder
     */
    private function resolveTenant(Request $request): ?Tenant
    {
        // 1. Header'dan tenant ID
        if ($tenantId = $request->header('X-Tenant-ID')) {
            return $this->getTenantById($tenantId);
        }

        // 2. Subdomain'den tenant
        if ($tenant = $this->getTenantBySubdomain($request)) {
            return $tenant;
        }

        // 3. API key'den tenant
        if ($apiKey = $request->bearerToken()) {
            return $this->getTenantByApiKey($apiKey);
        }

        // 4. Domain'den tenant
        return $this->getTenantByDomain($request->getHost());
    }

    /**
     * Tenant context'ini application'a set eder
     */
    private function setTenantContext(Tenant $tenant): void
    {
        // Global tenant instance'ını set et
        app()->instance('current_tenant', $tenant);

        // Database connection'ı tenant'a göre ayarla
        if ($tenant->database_name) {
            config(['database.connections.tenant' => [
                'driver' => 'pgsql',
                'host' => config('database.connections.pgsql.host'),
                'port' => config('database.connections.pgsql.port'),
                'database' => $tenant->database_name,
                'username' => config('database.connections.pgsql.username'),
                'password' => config('database.connections.pgsql.password'),
                'charset' => 'utf8',
                'prefix' => '',
                'search_path' => 'public',
                'sslmode' => 'prefer',
            ]]);

            DB::setDefaultConnection('tenant');
        }

        // Cache prefix'ini tenant'a göre ayarla
        Cache::setDefaultDriver('tenant');
        config(['cache.stores.tenant' => [
            'driver' => 'redis',
            'connection' => 'default',
            'prefix' => "tenant_{$tenant->id}_",
        ]]);
    }

    /**
     * ID ile tenant bulur
     */
    private function getTenantById(string $tenantId): ?Tenant
    {
        return Cache::remember("tenant_id_{$tenantId}", 300, function () use ($tenantId) {
            return Tenant::find($tenantId);
        });
    }

    /**
     * Subdomain ile tenant bulur
     */
    private function getTenantBySubdomain(Request $request): ?Tenant
    {
        $host = $request->getHost();
        $parts = explode('.', $host);

        if (count($parts) < 3) {
            return null; // No subdomain
        }

        $subdomain = $parts[0];

        return Cache::remember("tenant_subdomain_{$subdomain}", 300, function () use ($subdomain) {
            return Tenant::where('subdomain', $subdomain)->first();
        });
    }

    /**
     * API key ile tenant bulur
     */
    private function getTenantByApiKey(string $apiKey): ?Tenant
    {
        return Cache::remember("tenant_api_{$apiKey}", 300, function () use ($apiKey) {
            return Tenant::where('api_key', $apiKey)->first();
        });
    }

    /**
     * Domain ile tenant bulur
     */
    private function getTenantByDomain(string $domain): ?Tenant
    {
        return Cache::remember("tenant_domain_{$domain}", 300, function () use ($domain) {
            return Tenant::where('domain', $domain)->first();
        });
    }
}
