<?php

namespace App\Features\Admin\Package\Requests;

use Illuminate\Validation\Rule;

class UpdatePackageRequest extends StorePackageRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $rules = parent::rules();
        $package = $this->route('package');

        $rules['slug'] = [
            'required',
            'string',
            'max:255',
            'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
            Rule::unique('packages', 'slug')
                ->ignore($package?->id)
                ->where(fn ($query) => $query->whereNull('deleted_at')),
        ];

        return $rules;
    }
}
