<?php

namespace App\Features\Admin\Bank\Requests;

use App\Exceptions\ApiException;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class StoreBankRequest extends FormRequest
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
            'code' => ['required', 'string', 'max:50', 'unique:banks,code'],
            'name' => ['required', 'string', 'max:255'],
            'short_name' => ['nullable', 'string', 'max:255'],
            'logo' => ['nullable', 'string', 'max:500'],
            'bg_color' => ['nullable', 'string', 'max:20'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0', 'max:9999'],
            'limit_request_per_minute' => ['sometimes', 'integer', 'min:1', 'max:120'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'Mã ngân hàng là bắt buộc.',
            'code.unique' => 'Mã ngân hàng đã tồn tại.',
            'name.required' => 'Tên ngân hàng là bắt buộc.',
            'limit_request_per_minute.min' => 'Giới hạn request/phút phải lớn hơn hoặc bằng 1.',
            'limit_request_per_minute.max' => 'Giới hạn request/phút không được vượt quá 120.',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new ApiException($validator->errors()->first(), 422);
    }
}
