<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class MarketDatum extends Model
{
    use HasFactory;

    protected $table = 'market_data';

    protected $fillable = [
        'timestamp',
        'symbol',
        'open',
        'high',
        'low',
        'close',
        'volume',
        'indicators',
    ];

    protected $casts = [
        'timestamp' => 'datetime',
        'open' => 'decimal:8',
        'high' => 'decimal:8',
        'low' => 'decimal:8',
        'close' => 'decimal:8',
        'volume' => 'decimal:8',
        'indicators' => 'array',
    ];

    // Scopes
    public function scopeBySymbol(\Illuminate\Database\Eloquent\Builder $query, string $symbol): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('symbol', $symbol);
    }

    public function scopeByTimeRange(\Illuminate\Database\Eloquent\Builder $query, \Carbon\Carbon|string $start, \Carbon\Carbon|string $end): \Illuminate\Database\Eloquent\Builder
    {
        return $query->whereBetween('timestamp', [$start, $end]);
    }

    public function scopeLatest(\Illuminate\Database\Eloquent\Builder $query, ?string $symbol = null): \Illuminate\Database\Eloquent\Builder
    {
        if ($symbol) {
            $query = $query->where('symbol', $symbol);
        }

        return $query->orderBy('timestamp', 'desc');
    }
}
