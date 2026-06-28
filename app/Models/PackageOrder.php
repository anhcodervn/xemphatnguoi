<?php

namespace App\Models;

use App\Support\Enums\PackageOrderStatus;
use App\Support\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PackageOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'package_id',
        'source_subscription_id',
        'order_code',
        'price',
        'discount_amount',
        'credit_amount',
        'final_amount',
        'payment_method',
        'auto_renew_enabled',
        'payment_status',
        'status',
        'paid_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'credit_amount' => 'decimal:2',
            'final_amount' => 'decimal:2',
            'auto_renew_enabled' => 'boolean',
            'payment_status' => PaymentStatus::class,
            'status' => PackageOrderStatus::class,
            'paid_at' => 'datetime',
            'expires_at' => 'datetime',
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

    public function sourceSubscription(): BelongsTo
    {
        return $this->belongsTo(UserSubscription::class, 'source_subscription_id');
    }

    public function subscription(): HasOne
    {
        return $this->hasOne(UserSubscription::class, 'order_id');
    }

    public function couponLogs(): HasMany
    {
        return $this->hasMany(CouponLog::class);
    }
}
