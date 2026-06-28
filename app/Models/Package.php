<?php

namespace App\Models;

use App\Support\Enums\PackageStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Package extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'price',
        'duration_days',
        'account_limit',
        'can_buy_extra_account',
        'extra_account_price',
        'request_limit',
        'request_per_minute',
        'concurrent_limit',
        'features',
        'package_limits',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'duration_days' => 'integer',
            'account_limit' => 'integer',
            'can_buy_extra_account' => 'boolean',
            'extra_account_price' => 'decimal:2',
            'request_limit' => 'integer',
            'request_per_minute' => 'integer',
            'concurrent_limit' => 'integer',
            'features' => 'array',
            'package_limits' => 'array',
            'status' => PackageStatus::class,
            'deleted_at' => 'datetime',
        ];
    }

    public function userPackages(): HasMany
    {
        return $this->hasMany(UserPackage::class);
    }

    public function userSubscriptions(): HasMany
    {
        return $this->hasMany(UserSubscription::class);
    }

    public function packageOrders(): HasMany
    {
        return $this->hasMany(PackageOrder::class);
    }
}
