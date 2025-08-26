<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\UsageCounter;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Usage Enforcement Middleware
 * Enforces SaaS plan limits in real-time
 */
class UsageEnforcementMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $usageType): Response
    {
        $user = Auth::user();

        if (! $user || ! $user->tenant_id) {
            return response()->json([
                'error' => 'Tenant context required',
                'code' => 'TENANT_REQUIRED',
            ], 400);
        }

        $tenant = $user->tenant;
        $subscription = $user->activeSubscription();

        if (! $subscription) {
            return response()->json([
                'error' => 'Active subscription required',
                'code' => 'SUBSCRIPTION_REQUIRED',
            ], 402);
        }

        // Get plan limits
        $planConfig = config("billing.plans.{$subscription->plan}");
        if (! $planConfig) {
            Log::error('Invalid subscription plan', [
                'user_id' => $user->id,
                'tenant_id' => $tenant->id,
                'plan' => $subscription->plan,
            ]);

            return response()->json([
                'error' => 'Invalid subscription plan',
                'code' => 'INVALID_PLAN',
            ], 500);
        }

        // Check usage limits
        $limitResult = $this->checkUsageLimit($tenant, $usageType, $planConfig['limits'] ?? []);

        if (! $limitResult['allowed']) {
            Log::warning('Usage limit exceeded', [
                'user_id' => $user->id,
                'tenant_id' => $tenant->id,
                'usage_type' => $usageType,
                'current_usage' => $limitResult['current_usage'],
                'limit' => $limitResult['limit'],
                'plan' => $subscription->plan,
            ]);

            return response()->json([
                'error' => 'Usage limit exceeded',
                'code' => 'USAGE_LIMIT_EXCEEDED',
                'details' => [
                    'usage_type' => $usageType,
                    'current_usage' => $limitResult['current_usage'],
                    'limit' => $limitResult['limit'],
                    'plan' => $subscription->plan,
                    'reset_period' => $limitResult['reset_period'] ?? 'monthly',
                ],
            ], 429);
        }

        // Process request
        $response = $next($request);

        // Increment usage counter after successful request
        if ($response->getStatusCode() < 400) {
            $this->incrementUsage($tenant, $usageType);
        }

        // Add usage headers
        $response->headers->set('X-Usage-Type', $usageType);
        $response->headers->set('X-Usage-Current', (string) $limitResult['current_usage']);
        $response->headers->set('X-Usage-Limit', (string) $limitResult['limit']);
        $response->headers->set('X-Usage-Remaining', (string) max(0, $limitResult['limit'] - $limitResult['current_usage']));

        return $response;
    }

    /**
     * Check if usage is within limits
     */
    private function checkUsageLimit($tenant, string $usageType, array $limits): array
    {
        $limit = $limits[$usageType] ?? -1; // -1 means unlimited

        if ($limit === -1) {
            return [
                'allowed' => true,
                'current_usage' => 0,
                'limit' => -1,
            ];
        }

        $currentUsage = $this->getCurrentUsage($tenant, $usageType);

        return [
            'allowed' => $currentUsage < $limit,
            'current_usage' => $currentUsage,
            'limit' => $limit,
            'reset_period' => 'monthly',
        ];
    }

    /**
     * Get current usage for tenant and type
     */
    private function getCurrentUsage($tenant, string $usageType): int
    {
        return UsageCounter::where('tenant_id', $tenant->id)
            ->where('usage_type', $usageType)
            ->where('period_start', '>=', now()->startOfMonth())
            ->sum('count') ?? 0;
    }

    /**
     * Increment usage counter
     */
    private function incrementUsage($tenant, string $usageType): void
    {
        try {
            $periodStart = now()->startOfMonth();

            UsageCounter::updateOrCreate(
                [
                    'tenant_id' => $tenant->id,
                    'usage_type' => $usageType,
                    'period_start' => $periodStart,
                ],
                [
                    'count' => \DB::raw('count + 1'),
                    'last_used_at' => now(),
                ]
            );

        } catch (\Exception $e) {
            // Don't fail the request if usage tracking fails
            Log::error('Failed to increment usage counter', [
                'tenant_id' => $tenant->id,
                'usage_type' => $usageType,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
