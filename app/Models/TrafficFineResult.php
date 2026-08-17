<?php

namespace App\Models;

use Database\Factories\TrafficFineResultFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrafficFineResult extends Model
{
    /** @use HasFactory<TrafficFineResultFactory> */
    use HasFactory;

    protected $fillable = [
        'plate',
        'vehicle_type',
        'status',
        'violation_count',
        'response_json',
        'provider',
        'checked_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'violation_count' => 'integer',
            'response_json' => 'array',
            'checked_at' => 'immutable_datetime',
            'expires_at' => 'immutable_datetime',
        ];
    }
}
