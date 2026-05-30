<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Support\Enums\SubscriptionStatus;

class UserSubscription extends Model
{
    /** @use HasFactory<\Database\Factories\UserSubscriptionFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'package_id',
        'order_id',
        'package_name',
        'package_price',
        'base_account_limit',
        'extra_account_limit',
        'used_account',
        'starts_at',
        'expires_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'package_price' => 'decimal:2',
            'base_account_limit' => 'integer',
            'extra_account_limit' => 'integer',
            'used_account' => 'integer',
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
            'status' => SubscriptionStatus::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(PackageOrder::class, 'order_id');
    }

    public function extraAccountOrders(): HasMany
    {
        return $this->hasMany(ExtraAccountOrder::class, 'user_subscription_id');
    }

    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class, 'subscription_id');
    }
}
