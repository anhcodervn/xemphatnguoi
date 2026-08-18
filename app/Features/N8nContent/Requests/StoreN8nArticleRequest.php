<?php

namespace App\Features\N8nContent\Requests;

use Closure;

class StoreN8nArticleRequest extends N8nFormRequest
{
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'excerpt' => ['nullable', 'string', 'max:5000'],
            'content' => [
                'required',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (! is_string($value) && ! is_array($value)) {
                        $fail('The content must be an HTML string or editor node array.');
                    }
                },
            ],
            'thumbnail' => ['nullable', 'url', 'max:2048'],
            'primary_keyword' => ['nullable', 'string', 'max:255'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:320'],
            'canonical_url' => ['nullable', 'url', 'max:2048'],
            'source' => ['nullable', 'array'],
            'source.type' => ['nullable', 'string', 'max:100'],
            'source.url' => ['nullable', 'url', 'max:5000'],
            'source.title' => ['nullable', 'string', 'max:255'],
            'source.domain' => ['nullable', 'string', 'max:255'],
            'source.external_id' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'array'],
            'category.id' => ['nullable', 'integer', 'exists:seo_categories,id'],
            'category.name' => ['required_with:category', 'string', 'max:255'],
            'category.slug' => ['nullable', 'string', 'max:255'],
            'category.description' => ['nullable', 'string', 'max:5000'],
            'category.parent_id' => ['nullable', 'integer', 'exists:seo_categories,id'],
            'tags' => ['nullable', 'array', 'max:50'],
            'tags.*' => ['string', 'max:100'],
            'sources' => ['nullable', 'array', 'max:20'],
            'sources.*.title' => ['nullable', 'string', 'max:255'],
            'sources.*.url' => ['required', 'url', 'max:5000'],
            'sources.*.domain' => ['nullable', 'string', 'max:255'],
            'sources.*.type' => ['nullable', 'string', 'max:100'],
        ];
    }
}
