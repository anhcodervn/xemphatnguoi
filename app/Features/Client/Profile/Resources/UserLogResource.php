<?php

namespace App\Features\Client\Profile\Resources;

use App\Models\UserLog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin UserLog
 */
class UserLogResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'time' => $this->created_at?->toISOString(),
            'action' => $this->action,
            'label' => $this->description ?: $this->action,
            'ip' => $this->ip,
            'device' => $this->deviceLabel(),
            'browser' => $this->browserLabel(),
            'status' => $this->statusLabel(),
        ];
    }

    private function browserLabel(): string
    {
        $userAgent = strtolower((string) $this->user_agent);

        return match (true) {
            str_contains($userAgent, 'edg') => 'Edge',
            str_contains($userAgent, 'firefox') => 'Firefox',
            str_contains($userAgent, 'safari') && ! str_contains($userAgent, 'chrome') => 'Safari',
            str_contains($userAgent, 'chrome') => 'Chrome',
            default => 'Unknown',
        };
    }

    private function deviceLabel(): string
    {
        $userAgent = strtolower((string) $this->user_agent);

        return match (true) {
            str_contains($userAgent, 'iphone') => 'iPhone',
            str_contains($userAgent, 'ipad') => 'iPad',
            str_contains($userAgent, 'android') => 'Android',
            str_contains($userAgent, 'macintosh') => 'Mac',
            str_contains($userAgent, 'windows') => 'Windows PC',
            default => 'Unknown device',
        };
    }

    private function statusLabel(): string
    {
        return str_contains((string) $this->description, 'thất bại') ? 'failed' : 'success';
    }
}
