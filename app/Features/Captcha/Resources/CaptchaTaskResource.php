<?php

namespace App\Features\Captcha\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CaptchaTaskResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $processingSeconds = $this->resolveProcessingSeconds();

        return [
            'id' => $this->id,
            'task_code' => $this->task_code,
            'external_task_id' => $this->external_task_id,
            'service_code' => $this->service_code,
            'status' => $this->status,
            'request_payload' => $this->request_payload,
            'result_payload' => $this->result_payload,
            'provider_cost' => $this->normalizeDecimalString($this->provider_cost),
            'selling_price' => $this->normalizeDecimalString($this->selling_price),
            'billing_source' => $this->billing_source,
            'package_subscription_id' => $this->package_subscription_id,
            'package_quota_consumed' => $this->package_quota_consumed,
            'error_message' => $this->error_message,
            'processing_seconds' => $processingSeconds,
            'processing_time_label' => $processingSeconds !== null ? $this->formatProcessingTimeLabel($processingSeconds) : null,
            'requested_at' => $this->requested_at?->toISOString(),
            'solved_at' => $this->solved_at?->toISOString(),
            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->user?->id,
                'username' => $this->user?->username,
                'full_name' => $this->user?->full_name,
                'email' => $this->user?->email,
            ]),
            'service' => $this->whenLoaded('service', fn () => CaptchaServiceResource::make($this->service)->resolve()),
            'source' => $this->whenLoaded('source', fn () => CaptchaSourceResource::make($this->source)->resolve()),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    private function normalizeDecimalString(mixed $value): string
    {
        if (! is_numeric($value)) {
            return (string) $value;
        }

        return rtrim(rtrim(number_format((float) $value, 4, '.', ''), '0'), '.');
    }

    private function resolveProcessingSeconds(): ?int
    {
        if (! $this->requested_at || ! $this->solved_at) {
            return null;
        }

        return max(1, $this->requested_at->diffInSeconds($this->solved_at));
    }

    private function formatProcessingTimeLabel(int $seconds): string
    {
        if ($seconds < 60) {
            return sprintf('%ds', $seconds);
        }

        $minutes = intdiv($seconds, 60);
        $remainingSeconds = $seconds % 60;

        if ($remainingSeconds === 0) {
            return sprintf('%dm', $minutes);
        }

        return sprintf('%dm %ds', $minutes, $remainingSeconds);
    }
}
