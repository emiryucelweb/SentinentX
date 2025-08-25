<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConsensusDecision extends Model
{
    protected $fillable = [
        'cycle_uuid', 'symbol', 'round1', 'round2', 'final_action', 'final_confidence', 'majority_lock',
    ];

    protected $casts = [
        'round1' => 'array',
        'round2' => 'array',
        'majority_lock' => 'boolean',
    ];
}
