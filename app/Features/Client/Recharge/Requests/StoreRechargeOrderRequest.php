<?php

namespace App\Features\Client\Recharge\Requests;

use App\Support\RechargeMethodCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRechargeOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rechargeMethodCatalog = app(RechargeMethodCatalog::class);
        $activeMethods = $rechargeMethodCatalog->activeMethodKeys();
        $methodConfig = $rechargeMethodCatalog->find((string) $this->input('method'));
        $minimumAmount = (float) ($methodConfig['minimum_amount'] ?? config('recharge.minimum_amount', 50_000));
        $maximumAmount = (float) ($methodConfig['maximum_amount'] ?? config('recharge.maximum_amount', 100_000_000));

        return [
            'method' => ['required', 'string', Rule::in($activeMethods)],
            'amount' => ['required', 'numeric', 'min:'.$minimumAmount, 'max:'.$maximumAmount],
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
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'method' => 'phương thức nạp',
            'amount' => 'số tiền nạp',
        ];
    }
}
