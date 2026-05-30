<?php

namespace App\Features\Client\ApiKey\Requests;

use App\Exceptions\ApiException;
use App\Models\ApiKey;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ApiKeyIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', Rule::in([
                ApiKey::STATUS_ACTIVE,
                ApiKey::STATUS_INACTIVE,
                ApiKey::STATUS_EXPIRED,
                ApiKey::STATUS_REVOKED,
            ])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [];
    }

    public function attributes(): array
    {
        return [
            'search' => 'từ khóa',
            'status' => 'trạng thái',
            'per_page' => 'số dòng mỗi trang',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new ApiException($validator->errors()->first(), 422);
    }
}
