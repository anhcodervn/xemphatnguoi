<?php

namespace App\Features\Captcha\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CaptchaServiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $settings = is_array($this->settings) ? $this->settings : [];
        $recentStats = is_array($this->getAttribute('recent_stats')) ? $this->getAttribute('recent_stats') : [];
        $processingTime = $recentStats['processing_time_label'] ?? $settings['speed_label'] ?? $this->formatSecondsLabel($this->estimated_seconds);
        $successRate = $recentStats['success_rate'] ?? $settings['success_rate'] ?? 99;

        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'category' => $this->category,
            'description' => $this->description,
            'provider_service_code' => $this->provider_service_code,
            'default_source_id' => $this->default_source_id,
            'sort_order' => (int) $this->sort_order,
            'base_price' => $this->normalizeDecimalString($this->base_price),
            'selling_price' => $this->normalizeDecimalString($this->selling_price),
            'estimated_seconds' => $this->estimated_seconds,
            'is_active' => $this->is_active,
            'settings' => $settings,
            'stats' => [
                'sample_size' => (int) ($recentStats['sample_size'] ?? 0),
                'completed_sample_size' => (int) ($recentStats['completed_sample_size'] ?? 0),
                'success_rate' => (int) $successRate,
                'processing_time_label' => (string) $processingTime,
                'avg_processing_seconds' => $recentStats['avg_processing_seconds'] ?? null,
            ],
            'source' => $this->whenLoaded('source', fn () => CaptchaSourceResource::make($this->source)->resolve()),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    private function formatSecondsLabel(?int $seconds): string
    {
        if (! $seconds) {
            return 'N/A';
        }

        return "{$seconds}s";
    }

    private function normalizeDecimalString(mixed $value): string
    {
        if (! is_numeric($value)) {
            return (string) $value;
        }

        return rtrim(rtrim(number_format((float) $value, 4, '.', ''), '0'), '.');
    }
}
