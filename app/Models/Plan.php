<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Plan extends Model
{
    use HasFactory;

    protected $table = 'plans';

    protected $fillable = [
        'name',
        'description',
        'price',
        'currency',
        'billing_cycle',
        'features',
        'limits',
        'meta',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'features' => 'array',
        'limits' => 'array',
        'meta' => 'array',
    ];

    // Relationships
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    // Scopes
    public function scopeActive(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('active', true);
    }

    public function scopeByPrice(\Illuminate\Database\Eloquent\Builder $query, float $minPrice, ?float $maxPrice = null): \Illuminate\Database\Eloquent\Builder
    {
        $query->where('price', '>=', $minPrice);
        if ($maxPrice) {
            $query->where('price', '<=', $maxPrice);
        }

        return $query;
    }
}
