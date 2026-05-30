<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CouponLog extends Model
{
    /** @use HasFactory<\Database\Factories\CouponLogFactory> */
    use HasFactory;

    protected $fillable = [
        'coupon_id',
        'user_id',
        'admin_id',
        'package_order_id',
        'action',
        'status',
        'order_amount',
        'discount_amount',
        'note',
        'payload',
    ];

    protected function casts(): array
    {
        return [
            'order_amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'payload' => 'array',
        ];
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function packageOrder(): BelongsTo
    {
        return $this->belongsTo(PackageOrder::class);
    }
}
