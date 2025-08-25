<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class LabTrade extends Model
{
    use HasFactory;

    protected $table = 'lab_trades';

    protected $fillable = [
        'symbol', 'side', 'qty', 'entry_price', 'exit_price', 'opened_at',
        'closed_at', 'pnl_quote', 'pnl_pct', 'cycle_uuid', 'meta',
    ];

    protected $casts = [
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
        'meta' => 'array',
    ];
}
