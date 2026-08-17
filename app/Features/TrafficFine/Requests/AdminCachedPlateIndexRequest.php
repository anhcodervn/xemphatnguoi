<?php

namespace App\Features\TrafficFine\Requests;

use App\Exceptions\ApiException;
use App\Features\TrafficFine\Enums\VehicleType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdminCachedPlateIndexRequest extends FormRequest
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
            'days' => ['nullable', 'integer', Rule::in([7, 30, 90])],
            'search' => ['nullable', 'string', 'max:16', 'regex:/^[0-9A-Za-zĐđ.\-\s]+$/u'],
            'state' => ['nullable', Rule::in(['all', 'active', 'expiring', 'expired'])],
            'vehicle_type' => ['nullable', Rule::in(VehicleType::values())],
            'status' => ['nullable', Rule::in(['success', 'no_violation'])],
            'provider' => ['nullable', 'string', 'max:64'],
            'sort' => ['nullable', Rule::in(['lookup_count', 'last_lookup_at', 'expires_at', 'checked_at', 'plate'])],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [];
    }

    public function attributes(): array
    {
        return [];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new ApiException($validator->errors()->first(), 422);
    }
}
