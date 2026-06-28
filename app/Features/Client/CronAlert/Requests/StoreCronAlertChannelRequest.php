<?php

namespace App\Features\Client\CronAlert\Requests;

use App\Support\Enums\CronAlertChannelType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCronAlertChannelRequest extends FormRequest
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
            'type' => ['required', Rule::enum(CronAlertChannelType::class)],
            'target_url' => ['nullable', 'url', 'max:2048'],
            'telegram_bot_token' => ['nullable', 'string', 'max:255'],
            'telegram_chat_id' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'events' => ['nullable', 'array'],
            'events.*' => ['string', 'in:on_fail,on_recovered,on_timeout,on_status_code_mismatch,on_body_mismatch'],
            'is_enabled' => ['nullable', 'boolean'],
        ];
    }
}
