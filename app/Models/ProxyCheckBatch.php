<?php

namespace App\Models;

use Database\Factories\ProxyCheckBatchFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProxyCheckBatch extends Model
{
    /** @use HasFactory<ProxyCheckBatchFactory> */
    use HasFactory, HasUlids;

    public const STATUS_PENDING = 'pending';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_COMPLETED = 'completed';

    protected $fillable = [
        'user_id',
        'status',
        'total',
        'processed',
        'live',
        'die',
        'completed_at',
    ];

    protected $attributes = [
        'status' => self::STATUS_PENDING,
        'processed' => 0,
        'live' => 0,
        'die' => 0,
    ];

    protected function casts(): array
    {
        return [
            'total' => 'integer',
            'processed' => 'integer',
            'live' => 'integer',
            'die' => 'integer',
            'completed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ProxyCheckItem::class)->orderBy('position');
    }
}
