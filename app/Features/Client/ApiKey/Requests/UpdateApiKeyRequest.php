<?php

namespace App\Features\Client\ApiKey\Requests;

use App\Exceptions\ApiException;
use App\Models\ApiKey;
use App\Support\ApiPermissionCatalog;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Validator as ValidatorFacade;
use Illuminate\Validation\Rule;

class UpdateApiKeyRequest extends FormRequest
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
            'name' => ['sometimes', 'required', 'string', 'max:100'],
            'permissions' => ['sometimes', 'required', 'array', 'min:1'],
            'permissions.*' => ['required', 'string', Rule::in(ApiPermissionCatalog::selfServiceKeys())],
            'ip_whitelist' => ['nullable', 'array', 'max:20'],
            'ip_whitelist.*' => [
                'required',
                'string',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $ip = trim((string) $value);

                    if ($ip === '*') {
                        return;
                    }

                    if (! ValidatorFacade::make(['ip' => $ip], ['ip' => ['ip']])->passes()) {
                        $fail('Danh sách IP cho phép phải là địa chỉ IP hợp lệ hoặc ký tự *.');
                    }
                },
            ],
            'expired_at' => ['nullable', 'date', 'after:now'],
            'status' => ['sometimes', 'required', 'string', Rule::in([
                ApiKey::STATUS_ACTIVE,
                ApiKey::STATUS_INACTIVE,
                ApiKey::STATUS_REVOKED,
            ])],
        ];
    }

    public function messages(): array
    {
        return [];
    }

    public function attributes(): array
    {
        return [
            'name' => 'tên API key',
            'permissions' => 'quyền API',
            'ip_whitelist' => 'danh sách IP cho phép',
            'expired_at' => 'thời gian hết hạn',
            'status' => 'trạng thái',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new ApiException($validator->errors()->first(), 422);
    }
}
