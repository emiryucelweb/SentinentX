<?php

namespace App\Models;

use App\Models\Concerns\HasTenantScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Trade extends Model
{
    use HasFactory, HasTenantScope;

    protected $fillable = [
        'tenant_id', 'symbol', 'side', 'status', 'margin_mode', 'leverage', 'qty', 'entry_price',
        'take_profit', 'stop_loss', 'pnl', 'pnl_realized', 'fees_total',
        'bybit_order_id', 'opened_at', 'closed_at', 'meta',
    ];

    // Prevent N+1 queries by default eager loading
    protected $with = ['tenant'];

    protected $casts = [
        'tenant_id' => 'string',
        'qty' => 'decimal:8',
        'entry_price' => 'decimal:8',
        'take_profit' => 'decimal:8',
        'stop_loss' => 'decimal:8',
        'pnl' => 'decimal:8',
        'pnl_realized' => 'decimal:8',
        'fees_total' => 'decimal:8',
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
        'meta' => 'array',
    ];

    /**
     * Get the tenant that owns this trade
     */
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Scope to get trades with optimized loading
     */
    public function scopeWithOptimizedLoading($query)
    {
        return $query->with(['tenant:id,name,settings']);
    }

    /**
     * Scope to filter by tenant
     */
    public function scopeByTenant($query, $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope to get recent trades efficiently
     */
    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days))
            ->orderBy('created_at', 'desc');
    }

    /**
     * Scope to get trades by status with pagination-friendly ordering
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status)
            ->orderBy('id', 'desc'); // Use ID instead of timestamps for better performance
    }
}
