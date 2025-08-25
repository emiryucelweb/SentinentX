<?php

declare(strict_types=1);

namespace App\Services\Billing;

use App\Models\UsageCounter;
use Illuminate\Support\Carbon;

final class UsageService
{
    public function increment(int $userId, string $service, string $period = 'daily'): UsageCounter
    {
        $now = Carbon::now();
        $resetAt = match ($period) {
            'daily' => $now->copy()->endOfDay(),
            'monthly' => $now->copy()->endOfMonth(),
            default => $now->copy()->endOfDay(),
        };

        $counter = UsageCounter::query()
            ->where('user_id', $userId)
            ->forService($service)
            ->forPeriod($period)
            ->first();

        if (! $counter) {
            $counter = UsageCounter::create([
                'user_id' => $userId,
                'service' => $service,
                'count' => 1,
                'period' => $period,
                'reset_at' => $resetAt,
            ]);
        } else {
            $counter->increment('count');
            if ($counter->reset_at && $counter->reset_at->lt($now)) {
                $counter->update(['count' => 1, 'reset_at' => $resetAt]);
            }
        }

        return $counter->refresh();
    }

    public function getCount(int $userId, string $service, string $period = 'daily'): int
    {
        return (int) UsageCounter::query()
            ->where('user_id', $userId)
            ->forService($service)
            ->forPeriod($period)
            ->value('count') ?? 0;
    }

    public function withinLimit(int $userId, string $service, int $limit, string $period = 'daily'): bool
    {
        return $this->getCount($userId, $service, $period) < $limit;
    }
}
