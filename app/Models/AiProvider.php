<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiProvider extends Model
{
    protected $fillable = [
        'name', 'enabled', 'model', 'timeout_ms', 'max_tokens', 'priority', 'weight', 'cost_per_1k_tokens', 'meta',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'weight' => 'decimal:2',
        'cost_per_1k_tokens' => 'decimal:4',
        'meta' => 'array',
    ];
}
