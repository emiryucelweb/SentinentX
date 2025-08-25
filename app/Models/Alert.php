<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Alert extends Model
{
    protected $fillable = [
        'type', 'message', 'severity', 'context', 'status',
        'acknowledged_by', 'acknowledged_at', 'resolved_by', 'resolved_at', 'resolution',
    ];

    protected $casts = [
        'context' => 'array',
        'acknowledged_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    public function acknowledgedBy(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'acknowledged_by');
    }

    public function resolvedBy(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }
}
