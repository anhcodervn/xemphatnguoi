<?php

namespace App\Features\N8nContent\Requests;

class StoreN8nCategoryRequest extends N8nFormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'parent_id' => ['nullable', 'integer', 'exists:seo_categories,id'],
        ];
    }
}
