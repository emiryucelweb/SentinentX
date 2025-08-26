<?php

declare(strict_types=1);

namespace App\Services\Billing;

use App\Models\Subscription;
use App\Models\UsageCounter;
use App\Models\User;
use Carbon\Carbon;

/**
 * SaaS Subscription Management Service
 * Handles subscription lifecycle, billing, and usage tracking
 */
class SubscriptionManager
{
    public function __construct(
        private UsageService $usageService
    ) {}

    /**
     * Create new subscription for user
     *
     * @param  array<string, mixed>  $features
     */
    public function createSubscription(User $user, string $planId, array $features = []): Subscription
    {
        $plans = config('billing.plans', []);
        $planConfig = $plans[$planId] ?? throw new \InvalidArgumentException("Unknown plan: $planId");

        return Subscription::create([
            'user_id' => $user->id,
            'tenant_id' => $user->tenant_id,
            'plan_id' => $planId,
            'plan_name' => $planConfig['name'],
            'status' => 'active',
            'price' => $planConfig['price'],
            'currency' => $planConfig['currency'] ?? 'USD',
            'billing_cycle' => $planConfig['billing_cycle'] ?? 'monthly',
            'features' => array_merge($planConfig['features'] ?? [], $features),
            'limits' => $planConfig['limits'] ?? [],
            'trial_ends_at' => $planConfig['trial_days'] ? now()->addDays($planConfig['trial_days']) : null,
            'current_period_start' => now(),
            'current_period_end' => $this->calculatePeriodEnd($planConfig['billing_cycle'] ?? 'monthly'),
            'meta' => [
                'created_by' => 'system',
                'signup_source' => 'web',
                'initial_plan' => $planId,
            ],
        ]);
    }

    /**
     * Check if user can access a feature
     */
    public function canUseFeature(User $user, string $feature): bool
    {
        $subscription = $user->activeSubscription();

        if (! $subscription) {
            // Free tier check
            $freeTier = config('billing.free_tier.features', []);

            return in_array($feature, $freeTier);
        }

        return in_array($feature, $subscription->features ?? []);
    }

    /**
     * Check usage limits
     *
     * @return array<string, mixed>
     */
    public function checkUsageLimit(User $user, string $service, int $requestedAmount = 1): array
    {
        $subscription = $user->activeSubscription();
        $limits = $subscription->limits ?? config('billing.free_tier.limits', []);

        $limit = $limits[$service] ?? 0;
        if ($limit <= 0) {
            return ['allowed' => false, 'reason' => 'Service not included in plan'];
        }

        $currentUsage = $this->usageService->getCount($user->id, $service);
        $newTotal = $currentUsage + $requestedAmount;

        if ($newTotal > $limit) {
            return [
                'allowed' => false,
                'reason' => 'Usage limit exceeded',
                'current_usage' => $currentUsage,
                'limit' => $limit,
                'requested' => $requestedAmount,
            ];
        }

        return [
            'allowed' => true,
            'current_usage' => $currentUsage,
            'limit' => $limit,
            'remaining' => $limit - $newTotal,
        ];
    }

    /**
     * Increment usage counter
     */
    public function recordUsage(User $user, string $service, int $amount = 1): void
    {
        $this->usageService->increment($user->id, $service, (string) $amount);
    }

    /**
     * Get subscription analytics
     *
     * @return array<string, mixed>
     */
    public function getAnalytics(User $user, ?string $period = 'current_month'): array
    {
        $subscription = $user->activeSubscription();
        if (! $subscription) {
            return ['subscription' => null, 'usage' => []];
        }

        [$start, $end] = $this->getPeriodDates($period);

        $usage = UsageCounter::where('user_id', $user->id)
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('service, SUM(count) as total')
            ->groupBy('service')
            ->pluck('total', 'service')
            ->toArray();

        return [
            'subscription' => [
                'plan' => $subscription->plan_name ?? 'Unknown',
                'status' => $subscription->status,
                'current_period_start' => $subscription->current_period_start,
                'current_period_end' => $subscription->current_period_end,
                'trial_ends_at' => $subscription->trial_ends_at,
            ],
            'usage' => $usage,
            'limits' => $subscription->limits ?? [],
            'period' => ['start' => $start, 'end' => $end],
        ];
    }

    /**
     * Handle subscription renewal
     */
    public function renewSubscription(Subscription $subscription): void
    {
        $billingCycle = $subscription->billing_cycle;

        $subscription->update([
            'current_period_start' => $subscription->current_period_end,
            'current_period_end' => $this->calculatePeriodEnd($billingCycle, $subscription->current_period_end),
            'updated_at' => now(),
        ]);

        // Reset usage counters for new period
        UsageCounter::where('user_id', $subscription->user_id)
            ->where('period', 'current')
            ->delete();
    }

    /**
     * Cancel subscription
     */
    public function cancelSubscription(Subscription $subscription, bool $immediately = false): void
    {
        if ($immediately) {
            $subscription->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'current_period_end' => now(),
            ]);
        } else {
            $subscription->update([
                'status' => 'cancel_at_period_end',
                'cancelled_at' => now(),
            ]);
        }
    }

    /**
     * Upgrade/downgrade subscription
     */
    public function changeSubscription(Subscription $subscription, string $newPlanId): Subscription
    {
        $plans = config('billing.plans', []);
        $newPlan = $plans[$newPlanId] ?? throw new \InvalidArgumentException("Unknown plan: $newPlanId");

        $subscription->update([
            'plan_id' => $newPlanId,
            'plan_name' => $newPlan['name'],
            'price' => $newPlan['price'],
            'features' => $newPlan['features'] ?? [],
            'limits' => $newPlan['limits'] ?? [],
            'meta' => array_merge($subscription->meta ?? [], [
                'previous_plan' => $subscription->plan_id,
                'changed_at' => now()->toISOString(),
            ]),
        ]);

        return $subscription->fresh();
    }

    /**
     * Calculate period end date
     */
    private function calculatePeriodEnd(string $billingCycle, ?Carbon $startDate = null): Carbon
    {
        $start = $startDate ?? now();

        return match ($billingCycle) {
            'monthly' => $start->copy()->addMonth(),
            'yearly' => $start->copy()->addYear(),
            'weekly' => $start->copy()->addWeek(),
            default => $start->copy()->addMonth(),
        };
    }

    /**
     * Get period date range
     */
    private function getPeriodDates(?string $period): array
    {
        return match ($period) {
            'current_month' => [now()->startOfMonth(), now()->endOfMonth()],
            'last_month' => [now()->subMonth()->startOfMonth(), now()->subMonth()->endOfMonth()],
            'current_year' => [now()->startOfYear(), now()->endOfYear()],
            'last_30_days' => [now()->subDays(30), now()],
            default => [now()->startOfMonth(), now()->endOfMonth()],
        };
    }
}
