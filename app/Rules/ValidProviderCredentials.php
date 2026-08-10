<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class ValidProviderCredentials implements ValidationRule
{
    private const MAX_BYTES = 16384;

    private const MAX_ITEMS = 50;

    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_array($value) || ($value !== [] && array_is_list($value))) {
            $fail('Credential phải là một JSON object.');

            return;
        }

        $encoded = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if (! is_string($encoded) || strlen($encoded) > self::MAX_BYTES) {
            $fail('Credential JSON không được vượt quá 16 KB.');

            return;
        }

        if (count($value) > self::MAX_ITEMS) {
            $fail('Credential JSON không được có quá 50 key.');

            return;
        }

        foreach ($value as $key => $secret) {
            if (! is_string($key)
                || $key === '__encrypted'
                || preg_match('/\A[a-zA-Z][a-zA-Z0-9_.-]{0,99}\z/', $key) !== 1) {
                $fail('Tên key credential phải bắt đầu bằng chữ và chỉ chứa chữ, số, dấu chấm, gạch ngang hoặc gạch dưới.');

                return;
            }

            if (! is_string($secret) || mb_strlen($secret) > 4096) {
                $fail('Mỗi giá trị credential phải là chuỗi không quá 4096 ký tự.');

                return;
            }

            if ($key === 'base_url') {
                $parts = parse_url($secret);
                $scheme = is_array($parts) ? ($parts['scheme'] ?? null) : null;
                $host = is_array($parts) ? ($parts['host'] ?? null) : null;

                if (! is_string($host) || ! in_array($scheme, ['http', 'https'], true) || mb_strlen($secret) > 500) {
                    $fail('base_url phải là URL HTTP hoặc HTTPS hợp lệ và không quá 500 ký tự.');

                    return;
                }
            }
        }
    }
}
