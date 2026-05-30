<?php

namespace App\Features\Admin\Mail\Requests;

use App\Exceptions\ApiException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class SendUserMailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'recipient_type' => ['required', 'string', 'in:all,users'],
            'user_ids' => ['nullable', 'array'],
            'user_ids.*' => ['integer', 'min:1', 'exists:users,id'],
            'subject' => ['required', 'string', 'max:255'],
            'title' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
            'cta_text' => ['nullable', 'string', 'max:100'],
            'cta_url' => ['nullable', 'url', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'recipient_type.required' => 'Vui lòng chọn đối tượng nhận.',
            'recipient_type.in' => 'Đối tượng nhận không hợp lệ.',
            'user_ids.array' => 'Danh sách người dùng không hợp lệ.',
            'user_ids.*.exists' => 'Người dùng không tồn tại.',
            'subject.required' => 'Vui lòng nhập tiêu đề email.',
            'title.required' => 'Vui lòng nhập heading email.',
            'message.required' => 'Vui lòng nhập nội dung email.',
            'cta_url.url' => 'Link CTA không đúng định dạng URL.',
        ];
    }

    public function attributes(): array
    {
        return [
            'recipient_type' => 'đối tượng nhận',
            'user_ids' => 'danh sách user',
            'subject' => 'tiêu đề',
            'title' => 'heading',
            'message' => 'nội dung',
        ];
    }

    protected function prepareForValidation(): void
    {
        $recipientType = (string) $this->input('recipient_type');
        $subject = trim((string) $this->input('subject'));
        $title = trim((string) $this->input('title'));
        $message = trim((string) $this->input('message'));
        $ctaText = trim((string) $this->input('cta_text'));
        $ctaUrl = trim((string) $this->input('cta_url'));

        $userIds = $this->input('user_ids', []);
        if (! is_array($userIds)) {
            $userIds = [];
        }

        $normalizedUserIds = collect($userIds)
            ->map(fn (mixed $id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();

        if ($recipientType !== 'users') {
            $normalizedUserIds = [];
        }

        $this->merge([
            'recipient_type' => $recipientType,
            'subject' => $subject,
            'title' => $title,
            'message' => $message,
            'cta_text' => $ctaText !== '' ? $ctaText : null,
            'cta_url' => $ctaUrl !== '' ? $ctaUrl : null,
            'user_ids' => $normalizedUserIds,
        ]);
    }

    public function withValidator(\Illuminate\Validation\Validator $validator): void
    {
        $validator->after(function (\Illuminate\Validation\Validator $validator): void {
            if ($this->input('recipient_type') === 'users' && count((array) $this->input('user_ids', [])) === 0) {
                $validator->errors()->add('user_ids', 'Vui lòng chọn ít nhất một người dùng.');
            }
        });
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new ApiException($validator->errors()->first(), 422);
    }
}
