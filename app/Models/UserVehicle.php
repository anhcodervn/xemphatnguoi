<?php

namespace App\Models;

use Database\Factories\UserVehicleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class UserVehicle extends Model
{
    /** @use HasFactory<UserVehicleFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'plate',
        'vehicle_type',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function monitoring(): HasOne
    {
        return $this->hasOne(VehicleMonitoring::class);
    }
}
