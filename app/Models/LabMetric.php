<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class LabMetric extends Model
{
    use HasFactory;

    protected $table = 'lab_metrics';

    protected $fillable = ['lab_run_id', 'as_of', 'equity', 'pf', 'maxdd_pct', 'sharpe', 'win_rate', 'avg_trade_pct', 'meta'];

    protected $casts = [
        'as_of' => 'date',
        'meta' => 'array',
    ];
}
