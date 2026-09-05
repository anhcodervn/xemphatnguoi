<?php

namespace App\Models;

use Database\Factories\TrafficFineProviderFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrafficFineProvider extends Model
{
    /** @use HasFactory<TrafficFineProviderFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'label',
        'driver',
        'api_url',
        'api_token',
        'timeout',
        'connect_timeout',
        'retry_times',
        'retry_sleep_ms',
    ];

    protected $hidden = [
        'api_url',
        'api_token',
    ];

    protected function casts(): array
    {
        return [
            'api_token' => 'encrypted',
            'timeout' => 'integer',
            'connect_timeout' => 'integer',
            'retry_times' => 'integer',
            'retry_sleep_ms' => 'integer',
        ];
    }
}
