<?php

namespace App\Features\Admin\Seo\Services;

use App\Exceptions\ApiException;
use App\Models\SeoPost;
use App\Models\SeoPostActivityLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ContentWorkflowService
{
    public function saveDraft(SeoPost $post, User $admin): SeoPost
    {
        $this->ensureNotPublished($post);

        return $this->transition($post, $admin, SeoPost::STATUS_DRAFT, 'edited_by_admin');
    }

    public function approve(SeoPost $post, User $admin): SeoPost
    {
        if (! in_array($post->status, [SeoPost::STATUS_DRAFT, SeoPost::STATUS_PENDING_REVIEW, SeoPost::STATUS_REJECTED], true)) {
            throw new ApiException('Chỉ bài nháp, chờ duyệt hoặc bị từ chối mới có thể duyệt.', 409);
        }

        return DB::transaction(function () use ($post, $admin): SeoPost {
            $oldStatus = $post->status;
            $post->forceFill([
                'status' => SeoPost::STATUS_APPROVED,
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
                'rejection_reason' => null,
            ])->save();
            $this->log($post, $admin, 'approved', $oldStatus, SeoPost::STATUS_APPROVED);

            return $this->load($post);
        });
    }

    public function reject(SeoPost $post, User $admin, string $reason): SeoPost
    {
        $this->ensureNotPublished($post);

        return DB::transaction(function () use ($post, $admin, $reason): SeoPost {
            $oldStatus = $post->status;
            $post->forceFill([
                'status' => SeoPost::STATUS_REJECTED,
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
                'rejection_reason' => trim($reason),
            ])->save();
            $this->log($post, $admin, 'rejected', $oldStatus, SeoPost::STATUS_REJECTED, [
                'rejection_reason' => trim($reason),
            ]);

            return $this->load($post);
        });
    }

    public function publish(SeoPost $post, User $admin): SeoPost
    {
        if ($post->status !== SeoPost::STATUS_APPROVED) {
            throw new ApiException('Bài viết phải được duyệt trước khi xuất bản.', 409);
        }

        return DB::transaction(function () use ($post, $admin): SeoPost {
            $post->forceFill([
                'status' => SeoPost::STATUS_PUBLISHED,
                'published_by' => $admin->id,
                'published_at' => now(),
                'canonical_url' => $post->canonical_url ?: url('/blog/'.$post->slug),
                'scheduled_at' => null,
            ])->save();
            $this->log($post, $admin, 'published', SeoPost::STATUS_APPROVED, SeoPost::STATUS_PUBLISHED);

            return $this->load($post);
        });
    }

    private function transition(SeoPost $post, User $admin, string $status, string $action): SeoPost
    {
        return DB::transaction(function () use ($post, $admin, $status, $action): SeoPost {
            $oldStatus = $post->status;
            $post->forceFill([
                'status' => $status,
                'published_by' => null,
                'published_at' => null,
            ])->save();
            $this->log($post, $admin, $action, $oldStatus, $status);

            return $this->load($post);
        });
    }

    /**
     * @param  array<string, mixed>|null  $metadata
     */
    private function log(SeoPost $post, User $admin, string $action, ?string $oldStatus, ?string $newStatus, ?array $metadata = null): void
    {
        SeoPostActivityLog::query()->create([
            'seo_post_id' => $post->id,
            'actor_type' => SeoPost::CREATOR_ADMIN,
            'actor_id' => $admin->id,
            'action' => $action,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'metadata' => $metadata,
        ]);
    }

    private function ensureNotPublished(SeoPost $post): void
    {
        if ($post->status === SeoPost::STATUS_PUBLISHED) {
            throw new ApiException('Bài viết đã xuất bản không thể chuyển ngược trạng thái.', 409);
        }
    }

    private function load(SeoPost $post): SeoPost
    {
        return $post->fresh()->load([
            'category:id,name,slug',
            'seoTags:id,name,slug',
            'sources:id,seo_post_id,title,url,domain,type',
            'activityLogs' => fn ($query) => $query->latest()->limit(30),
        ]);
    }
}
