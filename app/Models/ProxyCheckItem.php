<?php

namespace App\Models;

use Database\Factories\ProxyCheckItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProxyCheckItem extends Model
{
    /** @use HasFactory<ProxyCheckItemFactory> */
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_LIVE = 'live';

    public const STATUS_DIE = 'die';

    protected $fillable = [
        'proxy_check_batch_id',
        'position',
        'endpoint',
        'proxy',
        'status',
        'exit_ip',
        'latency_ms',
        'message',
        'started_at',
        'completed_at',
    ];

    protected $hidden = [
        'proxy',
    ];

    protected $attributes = [
        'status' => self::STATUS_PENDING,
    ];

    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'proxy' => 'encrypted',
            'latency_ms' => 'integer',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(ProxyCheckBatch::class, 'proxy_check_batch_id');
    }
}
