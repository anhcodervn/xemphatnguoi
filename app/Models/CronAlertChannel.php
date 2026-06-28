<?php

namespace App\Models;

use App\Support\Enums\CronAlertChannelType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class CronAlertChannel extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'cron_job_id',
        'name',
        'type',
        'target_url',
        'telegram_bot_token',
        'telegram_chat_id',
        'email',
        'events',
        'is_enabled',
    ];

    protected function casts(): array
    {
        return [
            'type' => CronAlertChannelType::class,
            'telegram_bot_token' => 'encrypted',
            'events' => 'array',
            'is_enabled' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function cronJob(): BelongsTo
    {
        return $this->belongsTo(CronJob::class);
    }

    public function cronJobs(): BelongsToMany
    {
        return $this->belongsToMany(CronJob::class, 'cron_job_alert_channel')
            ->withTimestamps();
    }
}
