<?php

namespace App\Features\Client\CronJob\Requests;

use App\Support\Enums\CronJobBodyType;
use App\Support\Enums\CronJobMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCronJobRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'group_name' => ['nullable', 'string', 'max:120'],
            'description' => ['nullable', 'string'],
            'url' => ['required', 'url', 'max:2048'],
            'method' => ['required', Rule::enum(CronJobMethod::class)],
            'headers' => ['nullable', 'array'],
            'body_type' => ['required', Rule::enum(CronJobBodyType::class)],
            'body' => ['nullable'],
            'query_params' => ['nullable', 'array'],
            'cron_expression' => ['nullable', 'string', 'max:255'],
            'interval_seconds' => ['nullable', 'integer', 'min:1'],
            'timezone' => ['nullable', 'timezone:all'],
            'timeout_seconds' => ['nullable', 'integer', 'min:1', 'max:120'],
            'connect_timeout_seconds' => ['nullable', 'integer', 'min:1', 'max:120'],
            'retry_count' => ['nullable', 'integer', 'min:0', 'max:10'],
            'retry_delay_seconds' => ['nullable', 'integer', 'min:1', 'max:3600'],
            'max_response_size_kb' => ['nullable', 'integer', 'min:1', 'max:1024'],
            'expected_status_codes' => ['nullable', 'array'],
            'expected_status_codes.*' => ['integer', 'min:100', 'max:599'],
            'expected_body_contains' => ['nullable', 'string', 'max:255'],
            'expected_body_not_contains' => ['nullable', 'string', 'max:255'],
            'follow_redirects' => ['nullable', 'boolean'],
            'verify_ssl' => ['nullable', 'boolean'],
            'alert_channel_ids' => ['nullable', 'array'],
            'alert_channel_ids.*' => ['integer', 'exists:cron_alert_channels,id'],
        ];
    }
}
