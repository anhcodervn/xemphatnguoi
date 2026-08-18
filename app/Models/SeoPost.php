<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SeoPost extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PENDING_REVIEW = 'pending_review';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_SCHEDULED = 'scheduled';

    public const CREATOR_N8N = 'n8n';

    public const CREATOR_ADMIN = 'admin';

    protected $fillable = [
        'seo_category_id',
        'title',
        'slug',
        'excerpt',
        'thumbnail',
        'content',
        'faq',
        'seo_title',
        'seo_description',
        'canonical_url',
        'og_image',
        'robots',
        'focus_keyword',
        'tags',
        'cover_alt',
        'article_schema',
        'breadcrumb_schema',
        'status',
        'source_type',
        'source_url',
        'source_url_hash',
        'source_title',
        'source_domain',
        'content_hash',
        'external_id',
        'created_by_type',
        'created_by_id',
        'reviewed_by',
        'reviewed_at',
        'published_by',
        'rejection_reason',
        'index_status',
        'published_at',
        'scheduled_at',
    ];

    protected function casts(): array
    {
        return [
            'content' => 'array',
            'faq' => 'array',
            'tags' => 'array',
            'article_schema' => 'boolean',
            'breadcrumb_schema' => 'boolean',
            'reviewed_at' => 'datetime',
            'published_at' => 'datetime',
            'scheduled_at' => 'datetime',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(SeoCategory::class, 'seo_category_id');
    }

    public function seoTags(): BelongsToMany
    {
        return $this->belongsToMany(SeoTag::class, 'seo_post_tag');
    }

    public function sources(): HasMany
    {
        return $this->hasMany(SeoPostSource::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(SeoPostActivityLog::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function publisher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by');
    }
}
