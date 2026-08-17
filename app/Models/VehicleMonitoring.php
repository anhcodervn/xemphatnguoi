<?php

namespace App\Models;

use Database\Factories\VehicleMonitoringFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VehicleMonitoring extends Model
{
    /** @use HasFactory<VehicleMonitoringFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'user_vehicle_id',
        'enabled',
        'last_checked_at',
        'last_violation_count',
    ];

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'last_checked_at' => 'immutable_datetime',
            'last_violation_count' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(UserVehicle::class, 'user_vehicle_id');
    }
}
