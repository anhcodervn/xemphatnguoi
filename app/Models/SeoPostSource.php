<?php

namespace App\Models;

use Database\Factories\SeoPostSourceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SeoPostSource extends Model
{
    /** @use HasFactory<SeoPostSourceFactory> */
    use HasFactory;

    protected $fillable = [
        'seo_post_id',
        'title',
        'url',
        'url_hash',
        'domain',
        'type',
    ];

    public function post(): BelongsTo
    {
        return $this->belongsTo(SeoPost::class, 'seo_post_id');
    }
}
