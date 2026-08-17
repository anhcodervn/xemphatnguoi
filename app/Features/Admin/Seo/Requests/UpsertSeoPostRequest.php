<?php

namespace App\Features\Admin\Seo\Requests;

use App\Exceptions\ApiException;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpsertSeoPostRequest extends FormRequest
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
        $postId = $this->route('seoPost')?->id ?? $this->route('seoPost');

        return [
            'seo_category_id' => ['nullable', 'exists:seo_categories,id'],
            'title' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('seo_posts', 'slug')->ignore($postId),
            ],
            'excerpt' => ['nullable', 'string'],
            'thumbnail' => ['nullable', 'url', 'max:2048'],
            'content' => ['nullable', 'array'],
            'faq' => ['nullable', 'array'],
            'faq.*.question' => ['required_with:faq', 'string', 'max:500'],
            'faq.*.answer' => ['required_with:faq', 'string', 'max:2000'],
            'seo_title' => ['nullable', 'string', 'max:255'],
            'seo_description' => ['nullable', 'string', 'max:320'],
            'canonical_url' => ['nullable', 'url', 'max:2048'],
            'og_image' => ['nullable', 'url', 'max:2048'],
            'robots' => ['required', Rule::in(['index,follow', 'noindex,follow'])],
            'focus_keyword' => ['nullable', 'string', 'max:255'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:100'],
            'cover_alt' => ['nullable', 'string', 'max:255'],
            'article_schema' => ['sometimes', 'boolean'],
            'breadcrumb_schema' => ['sometimes', 'boolean'],
            'status' => ['required', Rule::in(['draft', 'published', 'scheduled'])],
            'published_at' => ['nullable', 'date'],
            'scheduled_at' => ['nullable', 'date'],
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
            'seo_category_id' => 'danh mục SEO',
            'title' => 'tiêu đề bài viết',
            'slug' => 'slug',
            'excerpt' => 'mô tả ngắn',
            'thumbnail' => 'ảnh đại diện',
            'content' => 'nội dung chính',
            'faq' => 'câu hỏi thường gặp',
            'seo_title' => 'SEO title',
            'seo_description' => 'SEO description',
            'canonical_url' => 'canonical URL',
            'og_image' => 'ảnh Open Graph',
            'robots' => 'robots',
            'focus_keyword' => 'focus keyword',
            'tags' => 'thẻ bài viết',
            'cover_alt' => 'alt text ảnh đại diện',
            'article_schema' => 'schema bài viết',
            'breadcrumb_schema' => 'schema breadcrumb',
            'status' => 'trạng thái',
            'published_at' => 'thời gian publish',
            'scheduled_at' => 'thời gian hẹn lịch',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new ApiException($validator->errors()->first(), 422);
    }
}
