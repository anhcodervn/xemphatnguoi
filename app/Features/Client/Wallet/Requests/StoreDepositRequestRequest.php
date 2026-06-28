<?php

namespace App\Features\Client\Wallet\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDepositRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:1000', 'max:999999999'],
            'config_id' => ['nullable', 'integer', 'exists:config_recharge,id'],
        ];
    }
}
