<?php

namespace App\Features\Admin\Package\Requests;

use App\Support\Enums\PackageStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePackageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('packages', 'slug')->where(fn ($query) => $query->whereNull('deleted_at')),
            ],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'duration_days' => ['required', 'integer', 'min:1'],
            'account_limit' => ['nullable', 'integer', 'min:0'],
            'can_buy_extra_account' => ['nullable', 'boolean'],
            'extra_account_price' => ['nullable', 'numeric', 'min:0'],
            'request_limit' => ['nullable', 'integer', 'min:0'],
            'request_per_minute' => ['nullable', 'integer', 'min:1'],
            'concurrent_limit' => ['nullable', 'integer', 'min:1'],
            'features' => ['nullable', 'array'],
            'features.*' => ['string', 'max:255'],
            'package_limits' => ['nullable', 'array'],
            'status' => ['required', Rule::enum(PackageStatus::class)],
        ];
    }
}
