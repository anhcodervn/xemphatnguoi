<?php

namespace App\Features\Api\V1\Requests;

use App\Models\BankAccount;
use App\Utils\ApiResponse;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class StoreRechargeClientOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'bank_id' => [
                'required',
                'integer',
                Rule::exists(BankAccount::class, 'id')->where(function ($query): void {
                    $query
                        ->where('status', 'active')
                        ->where('user_id', $this->user()?->id);
                }),
            ],
            'amount' => ['required', 'numeric', 'min:10000', 'max:50000000'],
            'client_order_code' => ['nullable', 'string', 'max:100'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $amount = $this->input('amount');

        if (is_string($amount)) {
            $this->merge([
                'amount' => (float) str_replace([',', ' '], '', $amount),
            ]);
        }

        if ($this->filled('client_order_code')) {
            $this->merge([
                'client_order_code' => trim((string) $this->input('client_order_code')),
            ]);
        }
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'bank_id.required' => 'Tài khoản ngân hàng là bắt buộc.',
            'bank_id.exists' => 'Tài khoản ngân hàng không hợp lệ hoặc đã ngừng hoạt động.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'bank_id' => 'tài khoản ngân hàng',
            'amount' => 'số tiền nạp',
            'client_order_code' => 'mã đơn hàng phía đối tác',
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
