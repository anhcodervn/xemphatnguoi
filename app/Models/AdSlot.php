<?php

namespace App\Models;

use Database\Factories\AdSlotFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdSlot extends Model
{
    /** @use HasFactory<AdSlotFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'enabled',
        'device',
        'start_at',
        'end_at',
    ];

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'start_at' => 'immutable_datetime',
            'end_at' => 'immutable_datetime',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query
            ->where('enabled', true)
            ->where(function (Builder $builder): void {
                $builder->whereNull('start_at')->orWhere('start_at', '<=', now());
            })
            ->where(function (Builder $builder): void {
                $builder->whereNull('end_at')->orWhere('end_at', '>=', now());
            });
    }
}
