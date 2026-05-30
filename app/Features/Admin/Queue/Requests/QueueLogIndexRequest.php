<?php

namespace App\Features\Admin\Queue\Requests;

use App\Exceptions\ApiException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class QueueLogIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'queue' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'string', 'in:processing,success,failed'],
            'search' => ['nullable', 'string', 'max:255'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
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

    protected function prepareForValidation(): void
    {
        $this->merge([
            'queue' => trim((string) $this->input('queue', '')),
            'status' => trim((string) $this->input('status', '')),
            'search' => trim((string) $this->input('search', '')),
            'per_page' => (int) $this->input('per_page', 20),
        ]);
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new ApiException($validator->errors()->first(), 422);
    }
}
