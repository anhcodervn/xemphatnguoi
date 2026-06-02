<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Crypt;

class Webhook extends Model
{
    use HasFactory;

    private const SECRET_PREFIX = 'whsec:';

    private const SECRET_CIPHER = 'AES-256-CBC';

    protected $fillable = [
        'user_id',
        'bank_account_id',
        'name',
        'url',
        'secret_key',
        'event_keyword',
        'status',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(WebhookLog::class);
    }

    protected function secretKey(): Attribute
    {
        return Attribute::make(
            get: function (?string $value): ?string {
                if ($value === null || $value === '') {
                    return $value;
                }

                if (str_starts_with($value, self::SECRET_PREFIX)) {
                    return $this->decryptCompactSecret($value) ?? $value;
                }

                try {
                    return Crypt::decryptString($value);
                } catch (\Throwable) {
                    return $value;
                }
            },
            set: function (?string $value): ?string {
                if ($value === null || $value === '') {
                    return $value;
                }

                if (str_starts_with($value, self::SECRET_PREFIX)) {
                    return $value;
                }

                return $this->encryptCompactSecret($value);
            },
        );
    }

    private function encryptCompactSecret(string $value): string
    {
        $iv = random_bytes(openssl_cipher_iv_length(self::SECRET_CIPHER));
        $key = $this->secretKeyBytes();
        $cipherText = openssl_encrypt($value, self::SECRET_CIPHER, $key, OPENSSL_RAW_DATA, $iv);

        if ($cipherText === false) {
            throw new \RuntimeException('Không thể mã hóa webhook secret.');
        }

        $ivEncoded = base64_encode($iv);
        $cipherEncoded = base64_encode($cipherText);
        $mac = hash_hmac('sha256', $ivEncoded.'.'.$cipherEncoded, $key);
        $payload = json_encode([
            'iv' => $ivEncoded,
            'value' => $cipherEncoded,
            'mac' => $mac,
        ], JSON_UNESCAPED_SLASHES);

        if (! is_string($payload)) {
            throw new \RuntimeException('Không thể lưu webhook secret.');
        }

        return self::SECRET_PREFIX.$payload;
    }

    private function decryptCompactSecret(string $value): ?string
    {
        $payload = json_decode(substr($value, strlen(self::SECRET_PREFIX)), true);

        if (! is_array($payload)) {
            return null;
        }

        $ivEncoded = is_string($payload['iv'] ?? null) ? $payload['iv'] : null;
        $cipherEncoded = is_string($payload['value'] ?? null) ? $payload['value'] : null;
        $mac = is_string($payload['mac'] ?? null) ? $payload['mac'] : null;

        if ($ivEncoded === null || $cipherEncoded === null || $mac === null) {
            return null;
        }

        $key = $this->secretKeyBytes();
        $expectedMac = hash_hmac('sha256', $ivEncoded.'.'.$cipherEncoded, $key);

        if (! hash_equals($expectedMac, $mac)) {
            return null;
        }

        $iv = base64_decode($ivEncoded, true);
        $cipherText = base64_decode($cipherEncoded, true);

        if ($iv === false || $cipherText === false) {
            return null;
        }

        $plainText = openssl_decrypt($cipherText, self::SECRET_CIPHER, $key, OPENSSL_RAW_DATA, $iv);

        return $plainText === false ? null : $plainText;
    }

    private function secretKeyBytes(): string
    {
        $configuredKey = config('app.key');

        if (! is_string($configuredKey) || $configuredKey === '') {
            throw new \RuntimeException('APP_KEY chưa được cấu hình để mã hóa webhook secret.');
        }

        if (str_starts_with($configuredKey, 'base64:')) {
            $decoded = base64_decode(substr($configuredKey, 7), true);

            if ($decoded !== false && $decoded !== '') {
                return hash('sha256', $decoded, true);
            }
        }

        return hash('sha256', $configuredKey, true);
    }
}
