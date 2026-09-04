<?php

namespace App\Features\Admin\MonitoringPlan\Services;

use App\Exceptions\ApiException;
use App\Models\MonitoringPlan;
use App\Support\RichTextSanitizer;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class MonitoringPlanService
{
    public function __construct(private readonly RichTextSanitizer $richTextSanitizer) {}

    public function all(): Collection
    {
        return MonitoringPlan::query()->orderBy('sort_order')->orderBy('id')->get();
    }

    /** @param array<string, mixed> $payload */
    public function create(array $payload): MonitoringPlan
    {
        $payload = $this->normalize($payload);
        $payload['slug'] = $this->uniqueSlug((string) $payload['name']);

        return MonitoringPlan::query()->create($payload);
    }

    /** @param array<string, mixed> $payload */
    public function update(MonitoringPlan $plan, array $payload): MonitoringPlan
    {
        $payload = $this->normalize($payload);
        $payload['slug'] = $this->uniqueSlug((string) $payload['name'], $plan->id);
        $plan->update($payload);

        return $plan->refresh();
    }

    public function delete(MonitoringPlan $plan): void
    {
        if ($plan->subscriptions()->exists()) {
            throw new ApiException('Không thể xóa gói đã phát sinh đăng ký. Bạn có thể tắt gói này.', 422);
        }

        $plan->delete();
    }

    /** @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function normalize(array $payload): array
    {
        $isCustom = (bool) $payload['is_custom'];
        $payload['description'] = $this->richTextSanitizer->sanitize(
            isset($payload['description']) ? (string) $payload['description'] : null,
        );
        $payload['sort_order'] = (int) ($payload['sort_order'] ?? 0);

        if ($isCustom) {
            $payload['vehicle_limit'] = null;
            $payload['price'] = null;
            $payload['min_vehicle_count'] = max(20, (int) $payload['min_vehicle_count']);
        } else {
            $payload['unit_price'] = null;
            $payload['min_vehicle_count'] = 20;
        }

        return $payload;
    }

    private function uniqueSlug(string $name, ?int $exceptId = null): string
    {
        $base = Str::slug($name) ?: 'goi-theo-doi';
        $slug = $base;
        $suffix = 2;

        while (MonitoringPlan::query()->where('slug', $slug)->when($exceptId, fn ($query) => $query->whereKeyNot($exceptId))->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
