<?php

namespace App\Models;

use Database\Factories\LookupHistoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LookupHistory extends Model
{
    /** @use HasFactory<LookupHistoryFactory> */
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'traffic_fine_result_id',
        'plate',
        'vehicle_type',
        'violation_count',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'violation_count' => 'integer',
            'created_at' => 'immutable_datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function result(): BelongsTo
    {
        return $this->belongsTo(TrafficFineResult::class, 'traffic_fine_result_id');
    }
}
