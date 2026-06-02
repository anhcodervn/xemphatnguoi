<?php

namespace App\Utils;

use Illuminate\Support\Facades\Crypt;

class EncodeBank
{
    private const PREFIX = 'bank:enc:';

    private const LEGACY_PREFIX = 'bank:enc:';

    private const VERSION = 'v3';

    private const LEGACY_VERSION = 'v2';

    private const CIPHER = 'AES-256-CBC';

    private const LEGACY_KEY = 'e1af337ba9f96d15e66e71c4c14156e80cef73602b8c51572203f77b76999809';

    public static function encode(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value === '' || self::isEncoded($value)) {
            return $value;
        }

        $ivLength = openssl_cipher_iv_length(self::CIPHER);
        $iv = random_bytes($ivLength);
        $key = self::resolveKey();
        $cipherText = openssl_encrypt($value, self::CIPHER, $key, OPENSSL_RAW_DATA, $iv);

        if ($cipherText === false) {
            return null;
        }

        $ivEncoded = base64_encode($iv);
        $cipherEncoded = base64_encode($cipherText);
        $mac = hash_hmac('sha256', $ivEncoded.'.'.$cipherEncoded, $key);
        $payload = json_encode([
            'v' => self::VERSION,
            'iv' => $ivEncoded,
            'value' => $cipherEncoded,
            'mac' => $mac,
        ], JSON_UNESCAPED_SLASHES);

        return is_string($payload) ? self::PREFIX.$payload : null;
    }

    public static function decode(?string $value): ?string
    {
        if ($value === null || $value === '' || ! self::isEncoded($value)) {
            return $value;
        }

        try {
            if (self::isCurrentEncoded($value)) {
                return self::decodeCurrent($value);
            }

            return Crypt::decryptString(substr($value, strlen(self::LEGACY_PREFIX)));
        } catch (\Throwable) {
            return $value;
        }
    }

    /**
     * @param  array<int|string, mixed>  $payload
     * @return array<int|string, mixed>
     */
    public static function encodeArray(array $payload): array
    {
        $encoded = [];

        foreach ($payload as $key => $value) {
            $encoded[$key] = self::transform($value, true);
        }

        return $encoded;
    }

    /**
     * @param  array<int|string, mixed>  $payload
     * @return array<int|string, mixed>
     */
    public static function decodeArray(array $payload): array
    {
        $decoded = [];

        foreach ($payload as $key => $value) {
            $decoded[$key] = self::transform($value, false);
        }

        return $decoded;
    }

    /**
     * @param  array<int|string, mixed>|string|null  $value
     */
    public static function encodeJson(array|string|null $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $payload = is_string($value) ? json_decode($value, true) : $value;

        if (! is_array($payload)) {
            return null;
        }

        return json_encode(
            self::encodeArray($payload),
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        ) ?: null;
    }

    /**
     * @return array<int|string, mixed>
     */
    public static function decodeJson(mixed $value): array
    {
        if (is_array($value)) {
            return self::decodeArray($value);
        }

        if (! is_string($value) || $value === '') {
            return [];
        }

        $decoded = json_decode($value, true);

        if (! is_array($decoded)) {
            return [];
        }

        return self::decodeArray($decoded);
    }

    public static function isEncoded(mixed $value): bool
    {
        return is_string($value) && str_starts_with($value, self::PREFIX);
    }

    private static function isCurrentEncoded(string $value): bool
    {
        $payload = json_decode(substr($value, strlen(self::PREFIX)), true);

        return is_array($payload)
            && is_string($payload['v'] ?? null)
            && in_array($payload['v'], [self::VERSION, self::LEGACY_VERSION], true);
    }

    private static function decodeCurrent(string $value): ?string
    {
        $payload = json_decode(substr($value, strlen(self::PREFIX)), true);

        if (! is_array($payload)) {
            return $value;
        }

        $ivEncoded = is_string($payload['iv'] ?? null) ? $payload['iv'] : null;
        $cipherEncoded = is_string($payload['value'] ?? null) ? $payload['value'] : null;
        $mac = is_string($payload['mac'] ?? null) ? $payload['mac'] : null;

        if ($ivEncoded === null || $cipherEncoded === null || $mac === null) {
            return $value;
        }

        $version = is_string($payload['v'] ?? null) ? $payload['v'] : self::LEGACY_VERSION;
        $key = self::resolveKey($version);
        $expectedMac = hash_hmac('sha256', $ivEncoded.'.'.$cipherEncoded, $key);

        if (! hash_equals($expectedMac, $mac)) {
            return $value;
        }

        $iv = base64_decode($ivEncoded, true);
        $cipherText = base64_decode($cipherEncoded, true);

        if ($iv === false || $cipherText === false) {
            return $value;
        }

        $plainText = openssl_decrypt($cipherText, self::CIPHER, $key, OPENSSL_RAW_DATA, $iv);

        return $plainText === false ? $value : $plainText;
    }

    private static function resolveKey(string $version = self::VERSION): string
    {
        if ($version === self::LEGACY_VERSION) {
            return hash('sha256', self::LEGACY_KEY, true);
        }

        $configuredKey = config('app.key');

        if (! is_string($configuredKey) || $configuredKey === '') {
            throw new \RuntimeException('APP_KEY chưa được cấu hình để mã hóa dữ liệu ngân hàng.');
        }

        if (str_starts_with($configuredKey, 'base64:')) {
            $decoded = base64_decode(substr($configuredKey, 7), true);

            if ($decoded !== false && $decoded !== '') {
                return hash('sha256', $decoded, true);
            }
        }

        return hash('sha256', $configuredKey, true);
    }

    private static function transform(mixed $value, bool $encode): mixed
    {
        if (is_array($value)) {
            return $encode ? self::encodeArray($value) : self::decodeArray($value);
        }

        if (! is_string($value)) {
            return $value;
        }

        return $encode ? self::encode($value) : self::decode($value);
    }
}
