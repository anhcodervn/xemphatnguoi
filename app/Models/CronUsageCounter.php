<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CronUsageCounter extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'date',
        'month',
        'total_runs',
        'successful_runs',
        'failed_runs',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'total_runs' => 'integer',
            'successful_runs' => 'integer',
            'failed_runs' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
