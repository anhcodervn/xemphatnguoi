<?php

namespace App\Models;

use Database\Factories\SeoTagFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class SeoTag extends Model
{
    /** @use HasFactory<SeoTagFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'created_by_type',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(SeoPost::class, 'seo_post_tag');
    }
}
