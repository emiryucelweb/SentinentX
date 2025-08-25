<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Setting extends Model
{
    use HasFactory;

    protected $table = 'settings';

    protected $fillable = [
        'user_id',
        'param_name',
        'param_value',
    ];

    protected $casts = [
        'param_value' => 'json',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeGlobal(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->whereNull('user_id');
    }

    public function scopeForUser(\Illuminate\Database\Eloquent\Builder $query, int|string|null $userId): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByName(\Illuminate\Database\Eloquent\Builder $query, string $name): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('param_name', $name);
    }

    // Helper methods
    public static function getValue(string $name, mixed $default = null, int|string|null $userId = null): mixed
    {
        $query = self::query()->where('param_name', $name);

        if ($userId) {
            $query->where('user_id', $userId);
        } else {
            $query->whereNull('user_id');
        }

        $setting = $query->first();

        return $setting ? $setting->param_value : $default;
    }

    public static function setValue(string $name, mixed $value, int|string|null $userId = null): self
    {
        return self::updateOrCreate(
            ['param_name' => $name, 'user_id' => $userId],
            ['param_value' => $value]
        );
    }
}
