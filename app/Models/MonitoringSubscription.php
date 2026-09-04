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

    public const STATUS_RENEWED = 'renewed';

    public const STATUS_UPGRADED = 'upgraded';

    protected $fillable = [
        'user_id',
        'monitoring_plan_id',
        'plan_name',
        'vehicle_limit',
        'unit_price',
        'total_price',
        'duration_days',
        'status',
        'auto_renew',
        'renewed_from_subscription_id',
        'upgraded_from_subscription_id',
        'renewal_count',
        'started_at',
        'expires_at',
        'last_renewed_at',
    ];

    protected $attributes = [
        'status' => self::STATUS_ACTIVE,
        'auto_renew' => false,
        'renewal_count' => 0,
    ];

    protected function casts(): array
    {
        return [
            'vehicle_limit' => 'integer',
            'unit_price' => 'decimal:2',
            'total_price' => 'decimal:2',
            'duration_days' => 'integer',
            'auto_renew' => 'boolean',
            'renewed_from_subscription_id' => 'integer',
            'upgraded_from_subscription_id' => 'integer',
            'renewal_count' => 'integer',
            'started_at' => 'immutable_datetime',
            'expires_at' => 'immutable_datetime',
            'last_renewed_at' => 'immutable_datetime',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query
            ->where('status', self::STATUS_ACTIVE)
            ->where('started_at', '<=', now())
            ->where('expires_at', '>', now());
    }

    public function scopeDueForRenewal(Builder $query): Builder
    {
        return $query
            ->where('status', self::STATUS_ACTIVE)
            ->where('auto_renew', true)
            ->where('expires_at', '<=', now());
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(MonitoringPlan::class, 'monitoring_plan_id');
    }

    public function renewedFrom(): BelongsTo
    {
        return $this->belongsTo(self::class, 'renewed_from_subscription_id');
    }

    public function upgradedFrom(): BelongsTo
    {
        return $this->belongsTo(self::class, 'upgraded_from_subscription_id');
    }
}
