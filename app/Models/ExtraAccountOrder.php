<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Support\Enums\ExtraAccountOrderStatus;

class ExtraAccountOrder extends Model
{
    /** @use HasFactory<\Database\Factories\ExtraAccountOrderFactory> */
    use HasFactory;

    protected $fillable = [
        'user_subscription_id',
        'quantity',
        'price',
        'status',
        'expired_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'price' => 'decimal:2',
            'status' => ExtraAccountOrderStatus::class,
            'expired_at' => 'datetime',
        ];
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(UserSubscription::class, 'user_subscription_id');
    }
}
