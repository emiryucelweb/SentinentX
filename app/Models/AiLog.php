<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiLog extends Model
{
    protected $fillable = [
        'cycle_uuid', 'symbol', 'provider', 'stage', 'action', 'confidence',
        'input_ctx', 'raw_output', 'latency_ms', 'reason',
    ];

    protected $casts = [
        'input_ctx' => 'array',
        'raw_output' => 'array',
    ];
}
