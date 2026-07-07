<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CaptchaTask extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_SOLVED = 'solved';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'user_id',
        'api_key_id',
        'captcha_service_id',
        'captcha_source_id',
        'task_code',
        'external_task_id',
        'service_code',
        'status',
        'request_payload',
        'result_payload',
        'provider_cost',
        'selling_price',
        'billing_source',
        'package_subscription_id',
        'package_quota_consumed',
        'error_message',
        'requested_at',
        'solved_at',
    ];

    protected function casts(): array
    {
        return [
            'request_payload' => 'array',
            'result_payload' => 'array',
            'provider_cost' => 'decimal:4',
            'selling_price' => 'decimal:4',
            'package_quota_consumed' => 'integer',
            'requested_at' => 'datetime',
            'solved_at' => 'datetime',
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

    public function service(): BelongsTo
    {
        return $this->belongsTo(CaptchaService::class, 'captcha_service_id');
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(CaptchaSource::class, 'captcha_source_id');
    }

    public function packageSubscription(): BelongsTo
    {
        return $this->belongsTo(UserSubscription::class, 'package_subscription_id');
    }
}
