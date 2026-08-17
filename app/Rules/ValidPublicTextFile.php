<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Str;
use Illuminate\Translation\PotentiallyTranslatedString;

class ValidPublicTextFile implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || $value === '') {
            return;
        }

        if (preg_match('//u', $value) !== 1) {
            $fail('Nội dung tệp phải sử dụng mã hóa UTF-8 hợp lệ.');

            return;
        }

        if (preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', $value) === 1) {
            $fail('Nội dung tệp không được chứa ký tự điều khiển.');

            return;
        }

        $lines = Str::of($value)
            ->replace(["\r\n", "\r"], "\n")
            ->explode("\n");

        if ($lines->count() > 1000) {
            $fail('Nội dung tệp không được vượt quá 1.000 dòng.');

            return;
        }

        if ($lines->contains(fn (string $line): bool => Str::length($line) > 2000)) {
            $fail('Mỗi dòng trong tệp không được vượt quá 2.000 ký tự.');
        }
    }
}
