<?php

namespace App\Models;

use App\Support\Enums\CronJobLogStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CronJobLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'cron_job_id',
        'user_id',
        'run_uuid',
        'attempt',
        'status',
        'method',
        'url',
        'status_code',
        'duration_ms',
        'request_headers',
        'request_body_preview',
        'response_headers',
        'response_body_preview',
        'response_size_bytes',
        'error_message',
        'ip_resolved',
        'started_at',
        'finished_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => CronJobLogStatus::class,
            'status_code' => 'integer',
            'duration_ms' => 'integer',
            'request_headers' => 'array',
            'response_headers' => 'array',
            'response_size_bytes' => 'integer',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function cronJob(): BelongsTo
    {
        return $this->belongsTo(CronJob::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
