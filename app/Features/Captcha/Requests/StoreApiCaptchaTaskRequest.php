<?php

namespace App\Features\Captcha\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreApiCaptchaTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'service_code' => ['required', 'string', 'exists:captcha_services,code'],
            'callback_url' => ['nullable', 'url', 'max:500'],
            'soft_id' => ['nullable', 'string', 'max:100'],
            'task' => ['required', 'array'],
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'status' => false,
            'message' => 'Dữ liệu gửi lên không hợp lệ.',
            'errors' => $validator->errors()->toArray(),
        ], 422));
    }
}
