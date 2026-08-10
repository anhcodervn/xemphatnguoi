<?php

namespace App\Features\Admin\Analytics\Requests;

use App\Exceptions\ApiException;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class TestDiscordWebhookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'webhook_index' => ['required', 'integer', 'min:0'],
            'event' => ['required', 'string', 'in:test_ping,user_registered,recharge_success'],
        ];
    }

    public function messages(): array
    {
        return [
            'webhook_index.required' => 'Vui lòng chọn webhook cần kiểm tra.',
            'event.required' => 'Vui lòng chọn loại sự kiện kiểm tra.',
            'event.in' => 'Loại sự kiện kiểm tra không hợp lệ.',
        ];
    }

    public function attributes(): array
    {
        return [
            'webhook_index' => 'webhook',
            'event' => 'sự kiện',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new ApiException($validator->errors()->first(), 422);
    }
}
