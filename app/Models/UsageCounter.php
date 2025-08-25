<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class UsageCounter extends Model
{
    use HasFactory;

    protected $table = 'usage_counters';

    protected $fillable = [
        'user_id',
        'service',
        'count',
        'period',
        'reset_at',
    ];

    protected $casts = [
        'count' => 'integer',
        'reset_at' => 'datetime',
    ];

    // Scopes
    public function scopeForService(\Illuminate\Database\Eloquent\Builder $query, string $service): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('service', $service);
    }

    public function scopeForPeriod(\Illuminate\Database\Eloquent\Builder $query, string $period): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('period', $period);
    }
}
