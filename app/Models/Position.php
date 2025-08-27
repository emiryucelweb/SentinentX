<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasTenantScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Position Model for SentinentX Trading Bot
 *
 * @property int $id
 * @property string $symbol
 * @property string $side
 * @property string $status
 * @property float $qty
 * @property float $entry_price
 * @property float|null $exit_price
 * @property float|null $pnl
 * @property float|null $fees
 * @property int|null $leverage
 * @property string|null $bybit_position_id
 * @property array|null $meta
 * @property \Illuminate\Support\Carbon|null $opened_at
 * @property \Illuminate\Support\Carbon|null $closed_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class Position extends Model
{
    use HasFactory, HasTenantScope;

    protected $fillable = [
        'user_id',
        'trade_id',
        'symbol',
        'side',
        'status',
        'qty',
        'entry_price',
        'exit_price',
        'pnl',
        'fees',
        'leverage',
        'take_profit',
        'stop_loss',
        'bybit_position_id',
        'meta',
        'opened_at',
        'closed_at',
    ];

    protected $casts = [
        'qty' => 'decimal:8',
        'entry_price' => 'decimal:8',
        'exit_price' => 'decimal:8',
        'pnl' => 'decimal:8',
        'fees' => 'decimal:8',
        'leverage' => 'integer',
        'take_profit' => 'decimal:8',
        'stop_loss' => 'decimal:8',
        'meta' => 'array',
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    /**
     * Position statuses
     */
    public const STATUS_OPEN = 'OPEN';

    public const STATUS_CLOSED = 'CLOSED';

    public const STATUS_LIQUIDATED = 'LIQUIDATED';

    public const STATUS_CANCELLED = 'CANCELLED';

    /**
     * Position sides
     */
    public const SIDE_LONG = 'Long';

    public const SIDE_SHORT = 'Short';

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function trade(): BelongsTo
    {
        return $this->belongsTo(Trade::class);
    }

    /**
     * Scope to get open positions
     */
    public function scopeOpen($query)
    {
        return $query->where('status', self::STATUS_OPEN);
    }

    /**
     * Scope to get closed positions
     */
    public function scopeClosed($query)
    {
        return $query->where('status', self::STATUS_CLOSED);
    }

    /**
     * Scope to filter by symbol
     */
    public function scopeBySymbol($query, string $symbol)
    {
        return $query->where('symbol', $symbol);
    }

    /**
     * Scope to get recent positions
     */
    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days))
            ->orderBy('created_at', 'desc');
    }

    /**
     * Check if position is profitable
     */
    public function isProfitable(): bool
    {
        return $this->pnl > 0;
    }

    /**
     * Get the risk percentage
     */
    public function getRiskPercentage(): float
    {
        if ($this->entry_price <= 0) {
            return 0;
        }

        return abs($this->pnl / ($this->qty * $this->entry_price)) * 100;
    }

    /**
     * Get position duration in minutes
     */
    public function getDurationMinutes(): int
    {
        $endTime = $this->closed_at ?? now();
        $startTime = $this->opened_at ?? $this->created_at;

        return $startTime->diffInMinutes($endTime);
    }

    /**
     * Format position for display
     */
    public function toDisplayArray(): array
    {
        return [
            'id' => $this->id,
            'symbol' => $this->symbol,
            'side' => $this->side,
            'status' => $this->status,
            'qty' => number_format($this->qty, 4),
            'entry_price' => number_format($this->entry_price, 4),
            'exit_price' => $this->exit_price ? number_format($this->exit_price, 4) : null,
            'pnl' => $this->pnl ? number_format($this->pnl, 4) : null,
            'leverage' => $this->leverage,
            'duration_minutes' => $this->getDurationMinutes(),
            'opened_at' => $this->opened_at?->format('Y-m-d H:i:s'),
            'closed_at' => $this->closed_at?->format('Y-m-d H:i:s'),
        ];
    }
}
