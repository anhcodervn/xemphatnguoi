<?php

namespace App\Features\Admin\Seo\Requests;

use App\Exceptions\ApiException;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpsertSeoCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $categoryId = $this->route('seoCategory')?->id ?? $this->route('seoCategory');

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('seo_categories', 'slug')->ignore($categoryId),
            ],
            'description' => ['nullable', 'string', 'max:5000'],
            'parent_id' => ['nullable', 'integer', 'exists:seo_categories,id', Rule::notIn(array_filter([$categoryId]))],
            'seo_title' => ['nullable', 'string', 'max:255'],
            'seo_description' => ['nullable', 'string', 'max:320'],
            'robots' => ['required', Rule::in(['index,follow', 'noindex,follow'])],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'created_by_type' => ['prohibited'],
        ];
    }

    public function messages(): array
    {
        return [
            'slug.regex' => 'Slug chỉ được chứa chữ thường, số và dấu gạch nối.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'tên danh mục',
            'slug' => 'slug',
            'seo_title' => 'SEO title',
            'seo_description' => 'SEO description',
            'robots' => 'robots',
            'is_active' => 'trạng thái hiển thị',
            'sort_order' => 'thứ tự hiển thị',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new ApiException($validator->errors()->first(), 422);
    }
}
