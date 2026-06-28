<?php

namespace App\Models;

use App\Support\Enums\CronJobBodyType;
use App\Support\Enums\CronJobLastStatus;
use App\Support\Enums\CronJobMethod;
use App\Support\Enums\CronJobStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CronJob extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'name',
        'group_name',
        'description',
        'url',
        'method',
        'headers',
        'body_type',
        'body',
        'query_params',
        'cron_expression',
        'interval_seconds',
        'timezone',
        'timeout_seconds',
        'connect_timeout_seconds',
        'retry_count',
        'retry_delay_seconds',
        'max_response_size_kb',
        'expected_status_codes',
        'expected_body_contains',
        'expected_body_not_contains',
        'follow_redirects',
        'verify_ssl',
        'status',
        'last_run_at',
        'next_run_at',
        'last_status',
        'consecutive_failures',
        'total_runs',
        'total_success',
        'total_failed',
    ];

    protected function casts(): array
    {
        return [
            'method' => CronJobMethod::class,
            'headers' => 'array',
            'body_type' => CronJobBodyType::class,
            'query_params' => 'array',
            'interval_seconds' => 'integer',
            'timeout_seconds' => 'integer',
            'connect_timeout_seconds' => 'integer',
            'retry_count' => 'integer',
            'retry_delay_seconds' => 'integer',
            'max_response_size_kb' => 'integer',
            'expected_status_codes' => 'array',
            'follow_redirects' => 'boolean',
            'verify_ssl' => 'boolean',
            'status' => CronJobStatus::class,
            'last_run_at' => 'datetime',
            'next_run_at' => 'datetime',
            'last_status' => CronJobLastStatus::class,
            'consecutive_failures' => 'integer',
            'total_runs' => 'integer',
            'total_success' => 'integer',
            'total_failed' => 'integer',
            'deleted_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(CronJobLog::class);
    }

    public function alertChannels(): BelongsToMany
    {
        return $this->belongsToMany(CronAlertChannel::class, 'cron_job_alert_channel')
            ->withTimestamps();
    }
}
