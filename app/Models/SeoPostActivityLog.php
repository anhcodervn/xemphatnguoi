<?php

namespace App\Models;

use Database\Factories\SeoPostActivityLogFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SeoPostActivityLog extends Model
{
    /** @use HasFactory<SeoPostActivityLogFactory> */
    use HasFactory;

    protected $fillable = [
        'seo_post_id',
        'actor_type',
        'actor_id',
        'action',
        'old_status',
        'new_status',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(SeoPost::class, 'seo_post_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
