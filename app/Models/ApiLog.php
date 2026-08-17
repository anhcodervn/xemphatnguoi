<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApiLog extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'api_key_id',
        'wallet_transaction_id',
        'endpoint',
        'method',
        'ip',
        'request_data',
        'service_response_data',
        'response_data',
        'status_code',
        'response_time_ms',
        'unit_price',
        'charged_amount',
        'billing_status',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'request_data' => 'array',
            'service_response_data' => 'array',
            'response_data' => 'array',
            'status_code' => 'integer',
            'response_time_ms' => 'integer',
            'unit_price' => 'decimal:2',
            'charged_amount' => 'decimal:2',
            'created_at' => 'datetime',
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

    public function walletTransaction(): BelongsTo
    {
        return $this->belongsTo(WalletTransaction::class);
    }
}
