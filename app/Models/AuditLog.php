<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Audit Log Model
 * Tracks all significant system activities for compliance and security
 */
class AuditLog extends Model
{
    /** @use HasFactory<\Database\Factories\AuditLogFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'tenant_id',
        'action',
        'resource_type',
        'resource_id',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'session_id',
        'request_id',
        'metadata',
    ];

    protected $casts = [
        'old_values' => 'json',
        'new_values' => 'json',
        'metadata' => 'json',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user that performed the action
     */
    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\User, \App\Models\AuditLog>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the tenant associated with the audit log
     */
    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Tenant, \App\Models\AuditLog>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Scope for filtering by action type
     */
    /**
     * @param  \Illuminate\Database\Eloquent\Builder<\App\Models\AuditLog>  $query
     * @return \Illuminate\Database\Eloquent\Builder<\App\Models\AuditLog>
     */
    public function scopeByAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope for filtering by resource type
     */
    /**
     * @param  \Illuminate\Database\Eloquent\Builder<\App\Models\AuditLog>  $query
     * @return \Illuminate\Database\Eloquent\Builder<\App\Models\AuditLog>
     */
    public function scopeByResourceType($query, string $resourceType)
    {
        return $query->where('resource_type', $resourceType);
    }

    /**
     * Scope for filtering by date range
     */
    /**
     * @param  \Illuminate\Database\Eloquent\Builder<\App\Models\AuditLog>  $query
     * @param  \Carbon\Carbon|string|null  $startDate
     * @param  \Carbon\Carbon|string|null  $endDate
     * @return \Illuminate\Database\Eloquent\Builder<\App\Models\AuditLog>
     */
    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Scope for filtering by user
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Static method to log an audit event
     */
    public static function logEvent(
        string $action,
        string $resourceType,
        ?int $resourceId = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?array $metadata = null
    ): self {
        return self::create([
            'user_id' => auth()->id(),
            'tenant_id' => session('tenant_id'),
            'action' => $action,
            'resource_type' => $resourceType,
            'resource_id' => $resourceId,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'session_id' => session()->getId(),
            'request_id' => request()->header('X-Request-ID'),
            'metadata' => $metadata,
        ]);
    }

    /**
     * Log trade creation
     */
    public static function logTradeCreated(Trade $trade): self
    {
        return self::logEvent(
            'trade.created',
            'Trade',
            $trade->id,
            null,
            $trade->toArray(),
            [
                'symbol' => $trade->symbol,
                'side' => $trade->side,
                'qty' => $trade->qty,
                'leverage' => $trade->leverage,
            ]
        );
    }

    /**
     * Log trade update
     */
    public static function logTradeUpdated(Trade $trade, array $oldValues): self
    {
        return self::logEvent(
            'trade.updated',
            'Trade',
            $trade->id,
            $oldValues,
            $trade->toArray(),
            [
                'symbol' => $trade->symbol,
                'changes' => array_keys($trade->getDirty()),
            ]
        );
    }

    /**
     * Log trade closure
     */
    public static function logTradeClosed(Trade $trade): self
    {
        return self::logEvent(
            'trade.closed',
            'Trade',
            $trade->id,
            null,
            $trade->toArray(),
            [
                'symbol' => $trade->symbol,
                'pnl' => $trade->pnl,
                'duration_minutes' => $trade->created_at?->diffInMinutes($trade->closed_at),
            ]
        );
    }

    /**
     * Log authentication events
     */
    public static function logAuth(string $action, ?User $user = null, ?array $metadata = null): self
    {
        return self::create([
            'user_id' => $user?->id,
            'tenant_id' => $user?->tenant_id ?? session('tenant_id'),
            'action' => "auth.{$action}",
            'resource_type' => 'User',
            'resource_id' => $user?->id,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'session_id' => session()->getId(),
            'request_id' => request()->header('X-Request-ID'),
            'metadata' => $metadata,
        ]);
    }

    /**
     * Log security events
     */
    public static function logSecurityEvent(string $event, ?array $metadata = null): self
    {
        return self::logEvent(
            "security.{$event}",
            'Security',
            null,
            null,
            null,
            $metadata
        );
    }
}
