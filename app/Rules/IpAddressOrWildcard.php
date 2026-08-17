<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class IpAddressOrWildcard implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ($value !== '*' && filter_var($value, FILTER_VALIDATE_IP) === false)) {
            $fail('Danh sách IP cho phép phải là địa chỉ IP hợp lệ hoặc ký tự *.');
        }
    }
}
