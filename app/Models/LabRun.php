<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class LabRun extends Model
{
    use HasFactory;

    protected $table = 'lab_runs';

    protected $fillable = [
        'symbols',
        'initial_equity',
        'final_equity',
        'risk_pct',
        'max_leverage',
        'total_trades',
        'winning_trades',
        'losing_trades',
        'final_pf',
        'start_date',
        'end_date',
        'status',
        'meta',
    ];

    protected $casts = [
        'symbols' => 'array',
        'initial_equity' => 'decimal:2',
        'final_equity' => 'decimal:2',
        'risk_pct' => 'decimal:2',
        'max_leverage' => 'integer',
        'total_trades' => 'integer',
        'winning_trades' => 'integer',
        'losing_trades' => 'integer',
        'final_pf' => 'decimal:6',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'meta' => 'array',
    ];

    // Relationships
    public function trades(): HasMany
    {
        return $this->hasMany(LabTrade::class, 'cycle_uuid', 'cycle_uuid');
    }

    public function metrics(): HasMany
    {
        return $this->hasMany(LabMetric::class, 'lab_run_id');
    }
}
