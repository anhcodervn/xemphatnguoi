<?php

namespace App\Features\Api\V1\Requests;

use App\Models\BankAccount;
use App\Utils\ApiResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class ListBankTransactionsRequest extends FormRequest
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
            'bank_id' => ['required', 'integer', Rule::exists(BankAccount::class, 'id')],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
            'force_refresh' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'bank_id.required' => 'Tài khoản ngân hàng là bắt buộc.',
            'bank_id.exists' => 'Tài khoản ngân hàng không hợp lệ.',
            'force_refresh.boolean' => "Trường force_refresh phải là true or false (1 hoặc 0 cũng được)"
        ];
    }

    public function attributes(): array
    {
        return [
            'bank_id' => 'tài khoản ngân hàng',
            'limit' => 'giới hạn',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json(
            ApiResponse::error(
                message: $validator->errors()->first(),
                data: [
                    'errors' => $validator->errors()->toArray(),
                ],
            ),
            422,
        ));
    }
}
