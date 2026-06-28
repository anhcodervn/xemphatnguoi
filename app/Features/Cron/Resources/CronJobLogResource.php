<?php

namespace App\Features\Cron\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CronJobLogResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'cron_job_id' => $this->cron_job_id,
            'user_id' => $this->user_id,
            'run_uuid' => $this->run_uuid,
            'attempt' => $this->attempt,
            'status' => $this->status?->value ?? $this->status,
            'method' => $this->method,
            'url' => $this->url,
            'status_code' => $this->status_code,
            'duration_ms' => $this->duration_ms,
            'request_headers' => $this->request_headers ?? [],
            'request_body_preview' => $this->request_body_preview,
            'response_headers' => $this->response_headers ?? [],
            'response_body_preview' => $this->response_body_preview,
            'response_size_bytes' => $this->response_size_bytes,
            'error_message' => $this->error_message,
            'ip_resolved' => $this->ip_resolved,
            'started_at' => $this->started_at?->toISOString(),
            'finished_at' => $this->finished_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
