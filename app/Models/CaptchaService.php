<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CaptchaService extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'category',
        'description',
        'provider_service_code',
        'default_source_id',
        'sort_order',
        'base_price',
        'selling_price',
        'estimated_seconds',
        'is_active',
        'settings',
    ];

    protected function casts(): array
    {
        return [
            'base_price' => 'decimal:4',
            'selling_price' => 'decimal:4',
            'sort_order' => 'integer',
            'estimated_seconds' => 'integer',
            'is_active' => 'boolean',
            'settings' => 'array',
        ];
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(CaptchaSource::class, 'default_source_id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(CaptchaTask::class);
    }
}
