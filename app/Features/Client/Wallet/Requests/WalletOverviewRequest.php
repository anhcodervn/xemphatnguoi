<?php

namespace App\Features\Client\Wallet\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WalletOverviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => ['nullable', 'numeric', 'min:0', 'max:999999999'],
        ];
    }
}
