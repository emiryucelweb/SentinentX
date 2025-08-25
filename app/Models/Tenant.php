<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tenant extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'domain',
        'database',
        'settings',
        'active',
        'meta',
    ];

    protected $casts = [
        'settings' => 'array',
        'meta' => 'array',
        'active' => 'boolean',
    ];

    /**
     * Get users belonging to this tenant
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get trades belonging to this tenant
     */
    public function trades(): HasMany
    {
        return $this->hasMany(Trade::class);
    }

    /**
     * Get subscriptions belonging to this tenant
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * Scope to filter by domain
     */
    public function scopeByDomain($query, string $domain)
    {
        return $query->where('domain', $domain);
    }

    /**
     * Scope to get only active tenants
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Check if tenant can perform action based on limits
     */
    public function canPerformAction(string $action, int $count = 1): bool
    {
        $settings = $this->settings ?? [];

        return match ($action) {
            'create_trade' => $this->checkDailyTradeLimit($count),
            'open_position' => $this->checkPositionLimit($count),
            'api_call' => $this->checkApiCallLimit($count),
            default => true,
        };
    }

    private function checkDailyTradeLimit(int $count): bool
    {
        $maxTrades = $this->settings['max_trades_per_day'] ?? 1000;
        $todayTrades = $this->trades()->whereDate('created_at', today())->count();

        return ($todayTrades + $count) <= $maxTrades;
    }

    private function checkPositionLimit(int $count): bool
    {
        $maxPositions = $this->settings['max_positions'] ?? 50;
        $openPositions = $this->trades()->where('status', 'OPEN')->count();

        return ($openPositions + $count) <= $maxPositions;
    }

    private function checkApiCallLimit(int $count): bool
    {
        $maxCalls = $this->settings['max_api_calls_per_minute'] ?? 600;

        // This would need Redis implementation for real rate limiting
        return true; // Simplified for now
    }
}
