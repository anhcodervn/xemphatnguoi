<?php

namespace App\Features\Client\Upload\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rules\File;

class StoreImageUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, array<int, mixed>|string>
     */
    public function rules(): array
    {
        return [
            'image' => [
                'required',
                File::image()->max(10 * 1024),
            ],
            'name' => [
                'nullable',
                'string',
                'max:120',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'image.required' => 'Ảnh tải lên là bắt buộc.',
            'name.max' => 'Tên ảnh không được vượt quá 120 ký tự.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'image' => 'ảnh tải lên',
            'name' => 'tên ảnh',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'status' => false,
            'message' => $validator->errors()->first(),
            'data' => [
                'errors' => $validator->errors()->toArray(),
            ],
        ], 422));
    }
}
