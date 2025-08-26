<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Concerns\HasTenantScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * SaaS User Model with Multi-tenancy Support
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $timezone
 * @property string|null $locale
 * @property int|null $tenant_id
 * @property string|null $role
 * @property array|null $meta
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Tenant|null $tenant
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Subscription> $subscriptions
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\UsageCounter> $usageCounters
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Trade> $trades
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Position> $positions
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\AiLog> $aiLogs
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static> byTenant(int $tenantId)
 * @method static \Illuminate\Database\Eloquent\Builder<static> byRole(string $role)
 * @method \App\Models\Subscription|null activeSubscription()
 */
class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, HasTenantScope, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'tenant_id',
        'role',
        'meta',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'meta' => 'array',
        ];
    }

    // Relationships
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function usageCounters(): HasMany
    {
        return $this->hasMany(UsageCounter::class);
    }

    public function settings(): HasMany
    {
        return $this->hasMany(Setting::class);
    }

    // Scopes
    public function scopeByTenant(\Illuminate\Database\Eloquent\Builder $query, int|string|null $tenantId): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeByRole(\Illuminate\Database\Eloquent\Builder $query, string $role): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('role', $role);
    }

    /**
     * Scope to get users with optimized loading
     */
    public function scopeWithOptimizedLoading($query)
    {
        return $query->with([
            'tenant:id,name,active,settings',
            'subscriptions' => function ($query) {
                $query->where('status', 'active')
                    ->select('id', 'user_id', 'plan', 'status', 'ends_at');
            },
        ]);
    }

    /**
     * Get active subscription efficiently
     */
    public function activeSubscription()
    {
        return $this->subscriptions()
            ->where('status', 'active')
            ->where('ends_at', '>', now())
            ->first();
    }

    /**
     * Get user trades with optimized loading
     */
    public function trades()
    {
        return $this->hasMany(Trade::class, 'tenant_id', 'tenant_id');
    }
}
