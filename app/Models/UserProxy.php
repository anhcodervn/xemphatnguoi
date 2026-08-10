<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserProxy extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_CHANGING = 'changing';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_DISABLED = 'disabled';

    public const STATUS_ERROR = 'error';

    protected $fillable = [
        'user_id',
        'proxy_product_id',
        'proxy_provider_id',
        'source_order_id',
        'provider_proxy_id',
        'provider_code',
        'label',
        'status',
        'country_code',
        'protocol',
        'host',
        'port',
        'username',
        'password',
        'response',
        'error_message',
        'expires_at',
        'last_changed_at',
    ];

    protected $attributes = [
        'status' => self::STATUS_ACTIVE,
    ];

    protected $hidden = [
        'provider_code',
        'response',
    ];

    protected function casts(): array
    {
        return [
            'provider_proxy_id' => 'encrypted',
            'host' => 'encrypted',
            'username' => 'encrypted',
            'password' => 'encrypted',
            'response' => 'encrypted:array',
            'port' => 'integer',
            'expires_at' => 'datetime',
            'last_changed_at' => 'datetime',
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

    public function sourceOrder(): BelongsTo
    {
        return $this->belongsTo(ProxyOrder::class, 'source_order_id');
    }
}
