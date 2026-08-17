<?php

namespace App\Models;

use Database\Factories\TrafficFineLookupLogFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrafficFineLookupLog extends Model
{
    /** @use HasFactory<TrafficFineLookupLogFactory> */
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'plate',
        'vehicle_type',
        'source',
        'cache_hit',
        'provider',
        'provider_latency_ms',
        'status',
        'ip',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'cache_hit' => 'boolean',
            'provider_latency_ms' => 'integer',
            'created_at' => 'immutable_datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
