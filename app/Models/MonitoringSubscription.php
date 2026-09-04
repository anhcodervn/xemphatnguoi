<?php

namespace App\Models;

use Database\Factories\MonitoringSubscriptionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonitoringSubscription extends Model
{
    /** @use HasFactory<MonitoringSubscriptionFactory> */
    use HasFactory;

    public const STATUS_ACTIVE = 'active';

    protected $fillable = [
        'user_id',
        'monitoring_plan_id',
        'plan_name',
        'vehicle_limit',
        'unit_price',
        'total_price',
        'duration_days',
        'status',
        'started_at',
        'expires_at',
    ];

    protected $attributes = [
        'status' => self::STATUS_ACTIVE,
    ];

    protected function casts(): array
    {
        return [
            'vehicle_limit' => 'integer',
            'unit_price' => 'decimal:2',
            'total_price' => 'decimal:2',
            'duration_days' => 'integer',
            'started_at' => 'immutable_datetime',
            'expires_at' => 'immutable_datetime',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query
            ->where('status', self::STATUS_ACTIVE)
            ->where('started_at', '<=', now())
            ->where('expires_at', '>', now());
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(MonitoringPlan::class, 'monitoring_plan_id');
    }
}
