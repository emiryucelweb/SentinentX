<?php

declare(strict_types=1);

namespace App\Services\Billing;

use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\UsageCounter;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Subscription ve billing yönetimi
 * SaaS plan'ları, usage tracking, billing cycle'ları yönetir
 */
class SubscriptionService
{
    /**
     * Tenant için subscription oluştur
     *
     * @param  array<string, mixed>  $features
     */
    public function createSubscription(
        Tenant $tenant,
        string $plan,
        array $features = [],
        ?Carbon $startsAt = null
    ): Subscription {
        $startsAt = $startsAt ?? now();

        $subscription = Subscription::create([
            'tenant_id' => $tenant->id,
            'plan' => $plan,
            'status' => 'active',
            'features' => $features,
            'starts_at' => $startsAt,
            'ends_at' => $this->calculateEndDate($plan, $startsAt),
            'trial_ends_at' => $this->calculateTrialEndDate($plan, $startsAt),
        ]);

        Log::info('Subscription created', [
            'tenant_id' => $tenant->id,
            'plan' => $plan,
            'subscription_id' => $subscription->id,
        ]);

        return $subscription;
    }

    /**
     * Subscription upgrade/downgrade
     */
    public function changePlan(Subscription $subscription, string $newPlan): Subscription
    {
        $oldPlan = $subscription->plan;

        // Prorated billing hesabı
        $proratedAmount = $this->calculateProration($subscription, $newPlan);

        $subscription->update([
            'plan' => $newPlan,
            'features' => $this->getPlanFeatures($newPlan),
            'ends_at' => $this->calculateEndDate($newPlan, $subscription->starts_at),
            'prorated_amount' => $proratedAmount,
        ]);

        Log::info('Subscription plan changed', [
            'subscription_id' => $subscription->id,
            'old_plan' => $oldPlan,
            'new_plan' => $newPlan,
            'prorated_amount' => $proratedAmount,
        ]);

        return $subscription->fresh() ?? $subscription;
    }

    /**
     * Usage tracking
     */
    public function trackUsage(
        Tenant $tenant,
        string $service,
        int $amount = 1,
        array $metadata = []
    ): UsageCounter {
        $period = now()->format('Y-m');

        $usage = UsageCounter::firstOrCreate([
            'tenant_id' => $tenant->id,
            'service' => $service,
            'period' => $period,
        ], [
            'count' => 0,
            'reset_at' => now()->endOfMonth(),
        ]);

        $usage->increment('count', $amount);

        // Usage limit kontrolü
        $this->checkUsageLimits($tenant, $service, $usage->count);

        Log::debug('Usage tracked', [
            'tenant_id' => $tenant->id,
            'service' => $service,
            'amount' => $amount,
            'total_usage' => $usage->count,
            'metadata' => $metadata,
        ]);

        return $usage;
    }

    /**
     * Usage limit'lerini kontrol eder
     */
    public function checkUsageLimits(Tenant $tenant, string $service, int $currentUsage): array
    {
        $subscription = $tenant->activeSubscription();
        if (! $subscription) {
            return ['status' => 'no_subscription', 'allowed' => false];
        }

        $limits = $this->getServiceLimits($subscription->plan, $service);

        if (! $limits) {
            return ['status' => 'unlimited', 'allowed' => true];
        }

        $usage_percentage = ($currentUsage / $limits['monthly_limit']) * 100;

        $result = [
            'status' => 'within_limits',
            'allowed' => true,
            'current_usage' => $currentUsage,
            'monthly_limit' => $limits['monthly_limit'],
            'usage_percentage' => round($usage_percentage, 2),
            'remaining' => max(0, $limits['monthly_limit'] - $currentUsage),
        ];

        // Warning thresholds
        if ($usage_percentage >= 90) {
            $result['status'] = 'limit_exceeded';
            $result['allowed'] = false;
        } elseif ($usage_percentage >= 80) {
            $result['status'] = 'approaching_limit';
            $result['warning'] = 'Usage is approaching monthly limit';
        }

        // Log kritik durum
        if (! $result['allowed']) {
            Log::warning('Usage limit exceeded', [
                'tenant_id' => $tenant->id,
                'service' => $service,
                'current_usage' => $currentUsage,
                'monthly_limit' => $limits['monthly_limit'],
            ]);
        }

        return $result;
    }

    /**
     * Billing cycle işlemleri
     */
    public function processBillingCycle(Subscription $subscription): array
    {
        if (! $subscription->isActive()) {
            return ['status' => 'skipped', 'reason' => 'inactive_subscription'];
        }

        $tenant = $subscription->tenant;
        $usage = $this->getMonthlyUsage($tenant);
        $amount = $this->calculateBillingAmount($subscription, $usage);

        // Invoice oluştur
        $invoice = $this->createInvoice($subscription, $amount, $usage);

        Log::info('Billing cycle processed', [
            'subscription_id' => $subscription->id,
            'tenant_id' => $tenant->id,
            'amount' => $amount,
            'invoice_id' => $invoice['id'] ?? null,
        ]);

        return [
            'status' => 'processed',
            'amount' => $amount,
            'invoice' => $invoice,
            'usage' => $usage,
        ];
    }

    /**
     * Tenant için aylık usage raporu
     */
    public function getMonthlyUsage(Tenant $tenant, ?string $period = null): array
    {
        $period = $period ?? now()->format('Y-m');

        $usage = UsageCounter::where('tenant_id', $tenant->id)
            ->where('period', $period)
            ->get()
            ->keyBy('service');

        return [
            'period' => $period,
            'tenant_id' => $tenant->id,
            'services' => $usage->mapWithKeys(function ($counter) {
                return [$counter->service => [
                    'count' => $counter->count,
                    'reset_at' => $counter->reset_at,
                ]];
            })->toArray(),
            'total_requests' => $usage->sum('count'),
        ];
    }

    /**
     * Plan features'ını döner
     */
    private function getPlanFeatures(string $plan): array
    {
        $features = [
            'starter' => [
                'api_requests_per_month' => 10000,
                'symbols_limit' => 5,
                'webhook_endpoints' => 2,
                'data_retention_days' => 30,
                'support_level' => 'basic',
            ],
            'professional' => [
                'api_requests_per_month' => 100000,
                'symbols_limit' => 20,
                'webhook_endpoints' => 10,
                'data_retention_days' => 90,
                'support_level' => 'priority',
                'custom_indicators' => true,
            ],
            'enterprise' => [
                'api_requests_per_month' => 1000000,
                'symbols_limit' => -1, // unlimited
                'webhook_endpoints' => -1, // unlimited
                'data_retention_days' => 365,
                'support_level' => 'dedicated',
                'custom_indicators' => true,
                'white_label' => true,
                'sla_guarantee' => '99.9%',
            ],
        ];

        return $features[$plan] ?? [];
    }

    /**
     * Service limit'lerini döner
     */
    private function getServiceLimits(string $plan, string $service): ?array
    {
        $limits = [
            'starter' => [
                'api_requests' => ['monthly_limit' => 10000],
                'ai_decisions' => ['monthly_limit' => 1000],
                'webhooks' => ['monthly_limit' => 5000],
            ],
            'professional' => [
                'api_requests' => ['monthly_limit' => 100000],
                'ai_decisions' => ['monthly_limit' => 10000],
                'webhooks' => ['monthly_limit' => 50000],
            ],
            'enterprise' => [
                // Unlimited for enterprise
            ],
        ];

        return $limits[$plan][$service] ?? null;
    }

    /**
     * End date hesaplar
     */
    private function calculateEndDate(string $plan, Carbon $startsAt): Carbon
    {
        // Çoğu plan aylık
        return $startsAt->copy()->addMonth();
    }

    /**
     * Trial end date hesaplar
     */
    private function calculateTrialEndDate(string $plan, Carbon $startsAt): ?Carbon
    {
        $trialDays = [
            'starter' => 14,
            'professional' => 30,
            'enterprise' => 30,
        ];

        $days = $trialDays[$plan] ?? 0;

        return $days > 0 ? $startsAt->copy()->addDays($days) : null;
    }

    /**
     * Prorated amount hesaplar
     */
    private function calculateProration(Subscription $subscription, string $newPlan): float
    {
        // Simplified prorated calculation
        $oldPrice = $this->getPlanPrice($subscription->plan);
        $newPrice = $this->getPlanPrice($newPlan);
        $remainingDays = now()->diffInDays($subscription->ends_at);
        $totalDays = $subscription->starts_at->diffInDays($subscription->ends_at);

        return ($newPrice - $oldPrice) * ($remainingDays / $totalDays);
    }

    /**
     * Plan price'ını döner
     */
    private function getPlanPrice(string $plan): float
    {
        $prices = [
            'starter' => 29.00,
            'professional' => 99.00,
            'enterprise' => 299.00,
        ];

        return $prices[$plan] ?? 0.0;
    }

    /**
     * Billing amount hesaplar
     */
    private function calculateBillingAmount(Subscription $subscription, array $usage): float
    {
        $basePrice = $this->getPlanPrice($subscription->plan);
        $overage = $this->calculateOverage($subscription, $usage);

        return $basePrice + $overage;
    }

    /**
     * Usage overage hesaplar
     */
    private function calculateOverage(Subscription $subscription, array $usage): float
    {
        $overage = 0.0;
        $features = $subscription->features;

        foreach ($usage['services'] as $service => $serviceUsage) {
            $limit = $this->getServiceLimits($subscription->plan, $service);
            if ($limit && $serviceUsage['count'] > $limit['monthly_limit']) {
                $excess = $serviceUsage['count'] - $limit['monthly_limit'];
                $overage += $excess * 0.001; // $0.001 per excess request
            }
        }

        return $overage;
    }

    /**
     * Invoice oluştur (mock)
     */
    private function createInvoice(Subscription $subscription, float $amount, array $usage): array
    {
        return [
            'id' => 'inv_'.uniqid(),
            'subscription_id' => $subscription->id,
            'tenant_id' => $subscription->tenant_id,
            'amount' => $amount,
            'period' => now()->format('Y-m'),
            'created_at' => now()->toISOString(),
            'due_date' => now()->addDays(30)->toISOString(),
            'status' => 'pending',
        ];
    }
}
