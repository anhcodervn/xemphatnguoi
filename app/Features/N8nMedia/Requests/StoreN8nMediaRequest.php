<?php

namespace App\Features\N8nMedia\Requests;

use App\Exceptions\ApiException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class StoreN8nMediaRequest extends FormRequest
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
            'image' => [
                'required',
                'string',
                'max:10000000',
            ],
            'code' => [
                'nullable',
                'string',
                'max:120',
                'regex:/^(?![.-])(?!.*[\/\\\\])(?=.{1,120}$)[A-Za-z0-9._-]+$/',
            ],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'image.required' => 'Ảnh base64 là bắt buộc.',
            'image.string' => 'Ảnh base64 phải là chuỗi ký tự.',
            'code.regex' => 'Mã ảnh không hợp lệ.',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new ApiException('Dữ liệu không hợp lệ.', 422, [
            'errors' => $validator->errors()->toArray(),
        ]);
    }
}
