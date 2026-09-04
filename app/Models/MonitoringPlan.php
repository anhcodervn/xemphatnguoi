<?php

namespace App\Models;

use Database\Factories\MonitoringPlanFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MonitoringPlan extends Model
{
    /** @use HasFactory<MonitoringPlanFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_custom',
        'vehicle_limit',
        'price',
        'unit_price',
        'min_vehicle_count',
        'duration_days',
        'is_active',
        'sort_order',
    ];

    protected $attributes = [
        'is_custom' => false,
        'min_vehicle_count' => 20,
        'duration_days' => 30,
        'is_active' => true,
        'sort_order' => 0,
    ];

    protected function casts(): array
    {
        return [
            'is_custom' => 'boolean',
            'vehicle_limit' => 'integer',
            'price' => 'decimal:2',
            'unit_price' => 'decimal:2',
            'min_vehicle_count' => 'integer',
            'duration_days' => 'integer',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(MonitoringSubscription::class);
    }
}
