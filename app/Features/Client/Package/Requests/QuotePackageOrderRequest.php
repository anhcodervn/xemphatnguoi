<?php

namespace App\Features\Client\Package\Requests;

use App\Models\Package;
use App\Support\Enums\PackageStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class QuotePackageOrderRequest extends FormRequest
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
            'coupon_code' => [
                'nullable',
                'string',
                'max:50',
            ],
        ];
    }
}
