<?php

declare(strict_types=1);

namespace App\Services\SaaS;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * SaaS Tenant Resource Manager
 * Handles resource allocation, limits, and monitoring per tenant
 */
class TenantResourceManager
{
    private const CACHE_TTL = 300; // 5 minutes

    // Resource limits by plan
    private const PLAN_LIMITS = [
        'starter' => [
            'max_active_positions' => 3,
            'max_ai_requests_per_hour' => 100,
            'max_api_calls_per_day' => 1000,
            'max_symbols' => 2,
            'max_leverage' => 5,
        ],
        'professional' => [
            'max_active_positions' => 10,
            'max_ai_requests_per_hour' => 500,
            'max_api_calls_per_day' => 10000,
            'max_symbols' => 5,
            'max_leverage' => 20,
        ],
        'institutional' => [
            'max_active_positions' => 50,
            'max_ai_requests_per_hour' => 2000,
            'max_api_calls_per_day' => 100000,
            'max_symbols' => 20,
            'max_leverage' => 50,
        ],
        'enterprise' => [
            'max_active_positions' => -1, // unlimited
            'max_ai_requests_per_hour' => -1,
            'max_api_calls_per_day' => -1,
            'max_symbols' => -1,
            'max_leverage' => 100,
        ],
    ];

    public function __construct(
        private readonly string $defaultPlan = 'starter'
    ) {}

    /**
     * Check if tenant can open a new position
     */
    public function canOpenPosition(string $tenantId): array
    {
        $plan = $this->getTenantPlan($tenantId);
        $limits = self::PLAN_LIMITS[$plan] ?? self::PLAN_LIMITS[$this->defaultPlan];

        $currentPositions = $this->getCurrentActivePositions($tenantId);
        $maxPositions = $limits['max_active_positions'];

        if ($maxPositions === -1) {
            return ['allowed' => true, 'reason' => 'unlimited'];
        }

        if ($currentPositions >= $maxPositions) {
            return [
                'allowed' => false,
                'reason' => 'max_positions_reached',
                'current' => $currentPositions,
                'limit' => $maxPositions,
                'plan' => $plan,
            ];
        }

        return [
            'allowed' => true,
            'current' => $currentPositions,
            'limit' => $maxPositions,
            'remaining' => $maxPositions - $currentPositions,
        ];
    }

    /**
     * Check if tenant can make AI request
     */
    public function canMakeAiRequest(string $tenantId): array
    {
        $plan = $this->getTenantPlan($tenantId);
        $limits = self::PLAN_LIMITS[$plan] ?? self::PLAN_LIMITS[$this->defaultPlan];

        $currentRequests = $this->getAiRequestsThisHour($tenantId);
        $maxRequests = $limits['max_ai_requests_per_hour'];

        if ($maxRequests === -1) {
            return ['allowed' => true, 'reason' => 'unlimited'];
        }

        if ($currentRequests >= $maxRequests) {
            return [
                'allowed' => false,
                'reason' => 'ai_rate_limit_exceeded',
                'current' => $currentRequests,
                'limit' => $maxRequests,
                'resets_at' => $this->getNextHourReset(),
            ];
        }

        return [
            'allowed' => true,
            'current' => $currentRequests,
            'limit' => $maxRequests,
            'remaining' => $maxRequests - $currentRequests,
        ];
    }

    /**
     * Validate trading parameters against tenant limits
     */
    public function validateTradingParameters(string $tenantId, array $params): array
    {
        $plan = $this->getTenantPlan($tenantId);
        $limits = self::PLAN_LIMITS[$plan] ?? self::PLAN_LIMITS[$this->defaultPlan];

        $violations = [];

        // Check leverage limit
        if (isset($params['leverage'])) {
            $maxLeverage = $limits['max_leverage'];
            if ($maxLeverage !== -1 && $params['leverage'] > $maxLeverage) {
                $violations[] = [
                    'parameter' => 'leverage',
                    'value' => $params['leverage'],
                    'limit' => $maxLeverage,
                    'message' => "Leverage {$params['leverage']} exceeds plan limit of {$maxLeverage}",
                ];
            }
        }

        // Check symbols limit
        if (isset($params['symbols'])) {
            $symbolCount = is_array($params['symbols']) ? count($params['symbols']) : 1;
            $maxSymbols = $limits['max_symbols'];
            if ($maxSymbols !== -1 && $symbolCount > $maxSymbols) {
                $violations[] = [
                    'parameter' => 'symbols',
                    'value' => $symbolCount,
                    'limit' => $maxSymbols,
                    'message' => "Symbol count {$symbolCount} exceeds plan limit of {$maxSymbols}",
                ];
            }
        }

        return [
            'valid' => empty($violations),
            'violations' => $violations,
            'plan' => $plan,
        ];
    }

    /**
     * Track resource usage
     */
    public function trackUsage(string $tenantId, string $resource, int $amount = 1): void
    {
        $hour = date('Y-m-d-H');
        $day = date('Y-m-d');

        $keys = [
            "usage:{$tenantId}:{$resource}:hour:{$hour}",
            "usage:{$tenantId}:{$resource}:day:{$day}",
        ];

        foreach ($keys as $key) {
            Cache::increment($key, $amount);
            Cache::put($key.'_ttl', true, 7200); // 2 hour expiry for cleanup
        }

        // Log usage for analytics
        Log::channel('analytics')->info('Resource usage tracked', [
            'tenant_id' => $tenantId,
            'resource' => $resource,
            'amount' => $amount,
            'hour' => $hour,
            'day' => $day,
        ]);
    }

    /**
     * Get comprehensive resource usage report
     */
    public function getUsageReport(string $tenantId): array
    {
        $plan = $this->getTenantPlan($tenantId);
        $limits = self::PLAN_LIMITS[$plan] ?? self::PLAN_LIMITS[$this->defaultPlan];

        $hour = date('Y-m-d-H');
        $day = date('Y-m-d');

        return [
            'tenant_id' => $tenantId,
            'plan' => $plan,
            'timestamp' => now()->toISOString(),
            'current_usage' => [
                'active_positions' => $this->getCurrentActivePositions($tenantId),
                'ai_requests_this_hour' => $this->getAiRequestsThisHour($tenantId),
                'api_calls_today' => $this->getApiCallsToday($tenantId),
            ],
            'limits' => $limits,
            'usage_percentage' => [
                'positions' => $this->calculateUsagePercentage(
                    $this->getCurrentActivePositions($tenantId),
                    $limits['max_active_positions']
                ),
                'ai_requests' => $this->calculateUsagePercentage(
                    $this->getAiRequestsThisHour($tenantId),
                    $limits['max_ai_requests_per_hour']
                ),
                'api_calls' => $this->calculateUsagePercentage(
                    $this->getApiCallsToday($tenantId),
                    $limits['max_api_calls_per_day']
                ),
            ],
            'warnings' => $this->getUsageWarnings($tenantId, $limits),
        ];
    }

    /**
     * Auto-scale tenant plan based on usage patterns
     */
    public function suggestPlanUpgrade(string $tenantId): ?array
    {
        $currentPlan = $this->getTenantPlan($tenantId);
        $usage = $this->getUsageReport($tenantId);

        $highUsageCount = 0;
        foreach ($usage['usage_percentage'] as $metric => $percentage) {
            if ($percentage > 80) { // Over 80% usage
                $highUsageCount++;
            }
        }

        if ($highUsageCount >= 2) {
            $suggestedPlan = $this->getNextPlanTier($currentPlan);
            if ($suggestedPlan) {
                return [
                    'current_plan' => $currentPlan,
                    'suggested_plan' => $suggestedPlan,
                    'reason' => 'High resource usage detected',
                    'usage_summary' => $usage['usage_percentage'],
                    'benefits' => $this->getPlanUpgradeBenefits($currentPlan, $suggestedPlan),
                ];
            }
        }

        return null;
    }

    private function getTenantPlan(string $tenantId): string
    {
        $cacheKey = "tenant_plan:{$tenantId}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($tenantId) {
            $tenant = DB::table('tenants')->where('id', $tenantId)->first();

            return $tenant->plan ?? $this->defaultPlan;
        });
    }

    private function getCurrentActivePositions(string $tenantId): int
    {
        return (int) DB::table('trades')
            ->where('tenant_id', $tenantId)
            ->where('status', 'OPEN')
            ->count();
    }

    private function getAiRequestsThisHour(string $tenantId): int
    {
        $hour = date('Y-m-d-H');
        $key = "usage:{$tenantId}:ai_requests:hour:{$hour}";

        return (int) Cache::get($key, 0);
    }

    private function getApiCallsToday(string $tenantId): int
    {
        $day = date('Y-m-d');
        $key = "usage:{$tenantId}:api_calls:day:{$day}";

        return (int) Cache::get($key, 0);
    }

    private function calculateUsagePercentage(int $current, int $limit): float
    {
        if ($limit === -1) {
            return 0.0; // Unlimited
        }

        if ($limit === 0) {
            return 100.0;
        }

        return round(($current / $limit) * 100, 2);
    }

    private function getUsageWarnings(string $tenantId, array $limits): array
    {
        $warnings = [];

        foreach ($limits as $resource => $limit) {
            if ($limit === -1) {
                continue;
            }

            $current = match ($resource) {
                'max_active_positions' => $this->getCurrentActivePositions($tenantId),
                'max_ai_requests_per_hour' => $this->getAiRequestsThisHour($tenantId),
                'max_api_calls_per_day' => $this->getApiCallsToday($tenantId),
                default => 0,
            };

            $percentage = $this->calculateUsagePercentage($current, $limit);

            if ($percentage >= 90) {
                $warnings[] = [
                    'resource' => $resource,
                    'usage_percentage' => $percentage,
                    'level' => 'critical',
                    'message' => "Resource {$resource} usage at {$percentage}%",
                ];
            } elseif ($percentage >= 75) {
                $warnings[] = [
                    'resource' => $resource,
                    'usage_percentage' => $percentage,
                    'level' => 'warning',
                    'message' => "Resource {$resource} usage at {$percentage}%",
                ];
            }
        }

        return $warnings;
    }

    private function getNextHourReset(): string
    {
        return now()->addHour()->startOfHour()->toISOString();
    }

    private function getNextPlanTier(string $currentPlan): ?string
    {
        $tiers = ['starter', 'professional', 'institutional', 'enterprise'];
        $currentIndex = array_search($currentPlan, $tiers);

        if ($currentIndex === false || $currentIndex >= count($tiers) - 1) {
            return null;
        }

        return $tiers[$currentIndex + 1];
    }

    private function getPlanUpgradeBenefits(string $currentPlan, string $suggestedPlan): array
    {
        $current = self::PLAN_LIMITS[$currentPlan] ?? [];
        $suggested = self::PLAN_LIMITS[$suggestedPlan] ?? [];

        $benefits = [];
        foreach ($suggested as $key => $value) {
            $currentValue = $current[$key] ?? 0;
            if ($value > $currentValue || $value === -1) {
                $benefits[$key] = [
                    'current' => $currentValue,
                    'upgraded' => $value === -1 ? 'unlimited' : $value,
                ];
            }
        }

        return $benefits;
    }
}
