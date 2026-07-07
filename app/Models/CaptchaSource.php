<?php

namespace App\Models;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Crypt;

class CaptchaSource extends Model
{
    use HasFactory;

    private const ENCRYPTED_CREDENTIALS_KEY = '__encrypted';

    protected $fillable = [
        'name',
        'driver',
        'api_base_url',
        'balance',
        'credentials',
        'settings',
        'priority',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'balance' => 'decimal:4',
            'settings' => 'array',
            'priority' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    protected function credentials(): Attribute
    {
        return Attribute::make(
            get: function (mixed $value): array {
                if ($value === null || $value === '') {
                    return [];
                }

                if (is_array($value)) {
                    if (isset($value[self::ENCRYPTED_CREDENTIALS_KEY]) && is_string($value[self::ENCRYPTED_CREDENTIALS_KEY])) {
                        try {
                            $decrypted = Crypt::decryptString($value[self::ENCRYPTED_CREDENTIALS_KEY]);
                            $decoded = json_decode($decrypted, true);

                            return is_array($decoded) ? $decoded : [];
                        } catch (DecryptException) {
                            return [];
                        }
                    }

                    return $value;
                }

                if (! is_string($value)) {
                    return [];
                }

                $decoded = json_decode($value, true);

                if (is_array($decoded)) {
                    if (isset($decoded[self::ENCRYPTED_CREDENTIALS_KEY]) && is_string($decoded[self::ENCRYPTED_CREDENTIALS_KEY])) {
                        try {
                            $decrypted = Crypt::decryptString($decoded[self::ENCRYPTED_CREDENTIALS_KEY]);
                            $decryptedDecoded = json_decode($decrypted, true);

                            return is_array($decryptedDecoded) ? $decryptedDecoded : [];
                        } catch (DecryptException) {
                            return [];
                        }
                    }

                    return $decoded;
                }

                try {
                    $legacyDecrypted = Crypt::decryptString($value);
                    $legacyDecoded = json_decode($legacyDecrypted, true);

                    return is_array($legacyDecoded) ? $legacyDecoded : [];
                } catch (DecryptException) {
                    return [];
                }
            },
            set: function (mixed $value): ?string {
                if ($value === null || $value === '' || $value === []) {
                    return null;
                }

                if (is_string($value)) {
                    $decoded = json_decode($value, true);
                    $value = is_array($decoded) ? $decoded : ['value' => $value];
                }

                if (! is_array($value)) {
                    return null;
                }

                return json_encode([
                    self::ENCRYPTED_CREDENTIALS_KEY => Crypt::encryptString(
                        json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                    ),
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            },
        );
    }

    public function services(): HasMany
    {
        return $this->hasMany(CaptchaService::class, 'default_source_id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(CaptchaTask::class);
    }
}
