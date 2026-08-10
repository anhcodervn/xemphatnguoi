<?php

namespace App\Models;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Crypt;

class ProxyProvider extends Model
{
    use HasFactory;

    public const DRIVER_GENERIC_REST = 'generic_rest';

    public const DRIVER_MANUAL = 'manual';

    public const DRIVER_PROXY_VN = 'proxy_vn';

    public const ORDER_METHOD_AUTOMATIC = 'automatic';

    public const ORDER_METHOD_MANUAL = 'manual';

    public const ORDER_METHODS = [
        self::ORDER_METHOD_AUTOMATIC,
        self::ORDER_METHOD_MANUAL,
    ];

    public const SUPPORTED_DRIVERS = [
        self::DRIVER_GENERIC_REST,
        self::DRIVER_MANUAL,
        self::DRIVER_PROXY_VN,
    ];

    private const ENCRYPTED_CREDENTIALS_KEY = '__encrypted';

    protected $fillable = [
        'name',
        'code',
        'order_method',
        'driver',
        'api_base_url',
        'balance',
        'credentials',
        'settings',
        'priority',
        'is_active',
    ];

    protected $hidden = [
        'credentials',
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

    protected function orderMethod(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value): string => is_string($value) && $value !== ''
                ? $value
                : ($this->driver === self::DRIVER_MANUAL ? self::ORDER_METHOD_MANUAL : self::ORDER_METHOD_AUTOMATIC),
        );
    }

    public function products(): HasMany
    {
        return $this->hasMany(ProxyProduct::class, 'default_provider_id');
    }
}
