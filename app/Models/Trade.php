<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Trade extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id', 'symbol', 'side', 'status', 'margin_mode', 'leverage', 'qty', 'entry_price',
        'take_profit', 'stop_loss', 'pnl', 'pnl_realized', 'fees_total',
        'bybit_order_id', 'opened_at', 'closed_at', 'meta',
    ];

    protected $casts = [
        'tenant_id' => 'string',
        'qty' => 'decimal:8',
        'entry_price' => 'decimal:8',
        'take_profit' => 'decimal:8',
        'stop_loss' => 'decimal:8',
        'pnl' => 'decimal:8',
        'pnl_realized' => 'decimal:8',
        'fees_total' => 'decimal:8',
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
        'meta' => 'array',
    ];

    /**
     * Get the tenant that owns this trade
     */
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
