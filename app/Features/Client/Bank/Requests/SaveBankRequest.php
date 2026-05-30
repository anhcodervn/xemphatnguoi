<?php

namespace App\Features\Client\Bank\Requests;

use App\Exceptions\ApiException;
use App\Models\Bank;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SaveBankRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $payload = [];

        if ($this->filled('bank_code')) {
            $payload['bank_code'] = Str::lower(trim($this->string('bank_code')->toString()));
        }

        if ($this->filled('display_name')) {
            $payload['display_name'] = trim($this->string('display_name')->toString());
        }

        if ($this->filled('username')) {
            $payload['username'] = trim($this->string('username')->toString());
        }

        if ($this->filled('password')) {
            $payload['password'] = trim($this->string('password')->toString());
        }

        if ($this->filled('account_number')) {
            $payload['account_number'] = preg_replace('/\s+/', '', $this->string('account_number')->toString()) ?? '';
        }

        if ($payload !== []) {
            $this->merge($payload);
        }
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $passwordRules = [
            'string',
            'max:255',
        ];

        if (! $this->isMethod('put')) {
            array_unshift($passwordRules, 'required');
        } else {
            array_unshift($passwordRules, 'nullable');
        }

        return [
            'bank_code' => [
                'required',
                'string',
                'max:50',
                Rule::exists(Bank::class, 'code')->where('is_active', true),
            ],
            'display_name' => [
                'required',
                'string',
                'min:2',
                'max:255',
            ],
            'username' => [
                'required',
                'string',
                'min:3',
                'max:255',
            ],
            'password' => [
                ...$passwordRules,
            ],
            'account_number' => [
                'required',
                'string',
                'regex:/^\d{6,30}$/',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'bank_code.required' => 'Ngân hàng là bắt buộc.',
            'bank_code.exists' => 'Ngân hàng không hợp lệ hoặc đang ngừng hỗ trợ.',
            'display_name.required' => 'Tên hiển thị là bắt buộc.',
            'display_name.min' => 'Tên hiển thị phải có ít nhất 2 ký tự.',
            'display_name.max' => 'Tên hiển thị không được vượt quá 255 ký tự.',
            'username.required' => 'Tên đăng nhập ngân hàng là bắt buộc.',
            'username.min' => 'Tên đăng nhập ngân hàng phải có ít nhất 3 ký tự.',
            'username.max' => 'Tên đăng nhập ngân hàng không được vượt quá 255 ký tự.',
            'password.required' => 'Mật khẩu đăng nhập ngân hàng là bắt buộc.',
            'password.max' => 'Mật khẩu đăng nhập ngân hàng không được vượt quá 255 ký tự.',
            'account_number.required' => 'Số tài khoản ngân hàng là bắt buộc.',
            'account_number.regex' => 'Số tài khoản ngân hàng chỉ được chứa chữ số và có độ dài từ 6 đến 30 ký tự.',
        ];
    }

    public function attributes(): array
    {
        return [
            'bank_code' => 'ngân hàng',
            'display_name' => 'tên hiển thị',
            'username' => 'tên đăng nhập ngân hàng',
            'password' => 'mật khẩu đăng nhập ngân hàng',
            'account_number' => 'số tài khoản ngân hàng',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new ApiException($validator->errors()->first(), 422, [
            'errors' => $validator->errors()->toArray(),
        ]);
    }
}
