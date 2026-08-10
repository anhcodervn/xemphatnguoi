<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProxyOrder extends Model
{
    use HasFactory;

    public const TYPE_PURCHASE = 'purchase';

    public const TYPE_CHANGE = 'change';

    public const TYPE_RENEW = 'renew';

    public const STATUS_PENDING = 'pending';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_FULFILLED = 'fulfilled';

    public const STATUS_FAILED = 'failed';

    public const STATUS_REFUNDED = 'refunded';

    protected $fillable = [
        'user_id',
        'proxy_product_id',
        'proxy_provider_id',
        'target_user_proxy_id',
        'order_code',
        'idempotency_key',
        'type',
        'status',
        'product_code',
        'product_name',
        'quantity',
        'duration_days',
        'country_code',
        'protocol',
        'unit_price',
        'total_amount',
        'external_order_id',
        'error_code',
        'error_message',
        'ordered_at',
        'fulfilled_at',
    ];

    protected $attributes = [
        'type' => self::TYPE_PURCHASE,
        'status' => self::STATUS_PENDING,
    ];

    protected function casts(): array
    {
        return [
            'target_user_proxy_id' => 'integer',
            'quantity' => 'integer',
            'duration_days' => 'integer',
            'unit_price' => 'decimal:4',
            'total_amount' => 'decimal:4',
            'external_order_id' => 'encrypted',
            'ordered_at' => 'datetime',
            'fulfilled_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(ProxyProduct::class, 'proxy_product_id');
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(ProxyProvider::class, 'proxy_provider_id');
    }

    public function userProxies(): HasMany
    {
        return $this->hasMany(UserProxy::class, 'source_order_id');
    }

    public function targetProxy(): BelongsTo
    {
        return $this->belongsTo(UserProxy::class, 'target_user_proxy_id');
    }
}
