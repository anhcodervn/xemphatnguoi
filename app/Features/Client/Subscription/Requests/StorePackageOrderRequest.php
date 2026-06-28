<?php

namespace App\Features\Client\Subscription\Requests;

use App\Models\Package;
use App\Support\Enums\PackageStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePackageOrderRequest extends FormRequest
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
            'package_id' => [
                'required',
                'integer',
                Rule::exists(Package::class, 'id')->where(fn ($query) => $query->where('status', PackageStatus::Active->value)),
            ],
            'discount_amount' => [
                'nullable',
                'numeric',
                'min:0',
            ],
            'coupon_code' => [
                'nullable',
                'string',
                'max:50',
            ],
            'payment_method' => [
                'nullable',
                'string',
                'max:255',
            ],
            'auto_renew_enabled' => [
                'nullable',
                'boolean',
            ],
        ];
    }
}
