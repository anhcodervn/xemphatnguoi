<?php

namespace App\Features\Cron\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CronJobResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'name' => $this->name,
            'group_name' => $this->group_name,
            'description' => $this->description,
            'url' => $this->url,
            'method' => $this->method?->value ?? $this->method,
            'headers' => $this->headers ?? [],
            'body_type' => $this->body_type?->value ?? $this->body_type,
            'body' => $this->body,
            'query_params' => $this->query_params ?? [],
            'cron_expression' => $this->cron_expression,
            'interval_seconds' => $this->interval_seconds,
            'timezone' => $this->timezone,
            'timeout_seconds' => $this->timeout_seconds,
            'connect_timeout_seconds' => $this->connect_timeout_seconds,
            'retry_count' => $this->retry_count,
            'retry_delay_seconds' => $this->retry_delay_seconds,
            'max_response_size_kb' => $this->max_response_size_kb,
            'expected_status_codes' => $this->expected_status_codes,
            'expected_body_contains' => $this->expected_body_contains,
            'expected_body_not_contains' => $this->expected_body_not_contains,
            'follow_redirects' => (bool) $this->follow_redirects,
            'verify_ssl' => (bool) $this->verify_ssl,
            'status' => $this->status?->value ?? $this->status,
            'last_run_at' => $this->last_run_at?->toISOString(),
            'next_run_at' => $this->next_run_at?->toISOString(),
            'last_status' => $this->last_status?->value ?? $this->last_status,
            'consecutive_failures' => $this->consecutive_failures,
            'total_runs' => $this->total_runs,
            'total_success' => $this->total_success,
            'total_failed' => $this->total_failed,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'user' => $this->whenLoaded('user', fn (): array => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
            ]),
            'alert_channels' => CronAlertChannelResource::collection($this->whenLoaded('alertChannels')),
        ];
    }
}
