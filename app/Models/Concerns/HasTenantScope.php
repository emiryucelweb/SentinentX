<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Global scope to automatically filter queries by tenant_id
 * Ensures complete tenant isolation across all models
 */
class TenantScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        // Get current tenant from context
        $tenantId = $this->getCurrentTenantId();

        if ($tenantId !== null) {
            $builder->where($model->getTable().'.tenant_id', $tenantId);
        }
    }

    /**
     * Extend the query builder with methods to bypass tenant scope
     */
    public function extend(Builder $builder): void
    {
        $builder->macro('withoutTenantScope', function (Builder $builder) {
            return $builder->withoutGlobalScope($this);
        });

        $builder->macro('forTenant', function (Builder $builder, $tenantId) {
            return $builder->withoutGlobalScope($this)
                ->where($builder->getModel()->getTable().'.tenant_id', $tenantId);
        });

        $builder->macro('allTenants', function (Builder $builder) {
            return $builder->withoutGlobalScope($this);
        });
    }

    /**
     * Get current tenant ID from various sources
     */
    private function getCurrentTenantId(): ?string
    {
        // Priority order:
        // 1. Explicitly set tenant context
        // 2. Authenticated user's tenant
        // 3. Request header
        // 4. Session

        // From tenant manager service
        if (app()->bound('tenant.manager')) {
            $tenantManager = app('tenant.manager');
            $tenantId = $tenantManager->getCurrentTenant();
            if ($tenantId && $tenantId !== 'default') {
                return $tenantId;
            }
        }

        // From authenticated user
        if (auth()->check() && auth()->user()->tenant_id) {
            return auth()->user()->tenant_id;
        }

        // From request header (API usage)
        if (request()->hasHeader('X-Tenant-ID')) {
            return request()->header('X-Tenant-ID');
        }

        // From session (web usage)
        if (session()->has('tenant_id')) {
            return session('tenant_id');
        }

        return null;
    }
}

/**
 * Trait for models that need tenant scoping
 */
trait HasTenantScope
{
    /**
     * Boot the has tenant scope trait for a model.
     */
    protected static function bootHasTenantScope(): void
    {
        static::addGlobalScope(new TenantScope);
    }

    /**
     * Get the tenant that owns this model
     */
    public function tenant()
    {
        return $this->belongsTo(\App\Models\Tenant::class);
    }

    /**
     * Scope to bypass tenant filtering (use with caution!)
     */
    public function scopeWithoutTenantScope($query)
    {
        return $query->withoutGlobalScope(TenantScope::class);
    }

    /**
     * Scope to explicitly filter by tenant
     */
    public function scopeForTenant($query, $tenantId)
    {
        return $query->withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenantId);
    }

    /**
     * Automatically set tenant_id when creating models
     */
    protected static function bootHasTenantScopeCreating(): void
    {
        static::creating(function ($model) {
            if (empty($model->tenant_id)) {
                $tenantScope = new TenantScope;
                $currentTenant = $tenantScope->getCurrentTenantId();

                if ($currentTenant) {
                    $model->tenant_id = $currentTenant;
                }
            }
        });
    }
}
