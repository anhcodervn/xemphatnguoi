<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class Coupon extends Model
{
    use HasFactory, SoftDeletes;

    public const TYPE_FIXED = 'fixed';

    public const TYPE_PERCENT = 'percent';

    protected $fillable = [
        'code',
        'name',
        'description',
        'type',
        'value',
        'min_order_amount',
        'max_discount_amount',
        'max_usage',
        'max_usage_per_user',
        'used_count',
        'starts_at',
        'expired_at',
        'first_order_only',
        'is_active',
        'requirements',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
            'min_order_amount' => 'decimal:2',
            'max_discount_amount' => 'decimal:2',
            'max_usage' => 'integer',
            'max_usage_per_user' => 'integer',
            'used_count' => 'integer',
            'starts_at' => 'datetime',
            'expired_at' => 'datetime',
            'first_order_only' => 'boolean',
            'is_active' => 'boolean',
            'requirements' => 'array',
        ];
    }

    public function logs(): HasMany
    {
        return $this->hasMany(CouponLog::class);
    }

    public function isExpired(): bool
    {
        return $this->expired_at instanceof Carbon && $this->expired_at->isPast();
    }

    public function isStarted(): bool
    {
        return $this->starts_at === null || $this->starts_at->lte(now());
    }

    public function isAvailable(): bool
    {
        if (! $this->is_active || ! $this->isStarted() || $this->isExpired()) {
            return false;
        }

        if ($this->max_usage !== null && $this->used_count >= $this->max_usage) {
            return false;
        }

        return true;
    }
}
