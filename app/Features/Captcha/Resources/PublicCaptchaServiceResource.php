<?php

namespace App\Features\Captcha\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PublicCaptchaServiceResource extends JsonResource
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
            'sort_order' => (int) $this->sort_order,
            'selling_price' => $this->normalizeDecimalString($this->selling_price),
            'estimated_seconds' => $this->estimated_seconds,
            'is_active' => $this->is_active,
            'settings' => [
                'icon_url' => $settings['icon_url'] ?? null,
                'request_example_body' => $settings['request_example_body'] ?? null,
            ],
            'stats' => [
                'sample_size' => (int) ($recentStats['sample_size'] ?? 0),
                'completed_sample_size' => (int) ($recentStats['completed_sample_size'] ?? 0),
                'success_rate' => (int) $successRate,
                'processing_time_label' => (string) $processingTime,
                'avg_processing_seconds' => $recentStats['avg_processing_seconds'] ?? null,
            ],
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
