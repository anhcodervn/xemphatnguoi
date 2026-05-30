<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QueueLog extends Model
{
    protected $fillable = [
        'job_uuid',
        'connection',
        'queue',
        'job_name',
        'status',
        'attempts',
        'payload',
        'error_message',
        'processing_at',
        'processed_at',
        'failed_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'processing_at' => 'datetime',
            'processed_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }
}
