<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RechargeClient extends Model
{
    /** @use HasFactory<\Database\Factories\RechargeClientFactory> */
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_PAID = 'paid';
    public const STATUS_FAILED = 'failed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_EXPIRED = 'expired';

    protected $table = 'recharge_client';

    protected $fillable = [
        'user_id',
        'api_key_id',
        'recharge_method_id',
        'bank_account_id',
        'matched_bank_transaction_id',
        'order_code',
        'client_order_code',
        'method',
        'method_label',
        'amount',
        'bank_name',
        'account_number',
        'account_name',
        'transfer_content',
        'status',
        'requested_at',
        'paid_at',
        'expires_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'requested_at' => 'datetime',
            'paid_at' => 'datetime',
            'expires_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function apiKey(): BelongsTo
    {
        return $this->belongsTo(ApiKey::class);
    }

    public function rechargeMethod(): BelongsTo
    {
        return $this->belongsTo(RechargeMethod::class);
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function matchedBankTransaction(): BelongsTo
    {
        return $this->belongsTo(BankTransaction::class, 'matched_bank_transaction_id');
    }
}
