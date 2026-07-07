<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ApiKey extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_REVOKED = 'revoked';

    public const TYPE_WALLET = 'wallet';

    public const TYPE_PACKAGE = 'package';

    protected $fillable = [
        'user_id',
        'key_type',
        'user_subscription_id',
        'name',
        'api_key',
        'api_secret_hash',
        'api_secret_encrypted',
        'permissions',
        'ip_whitelist',
        'status',
        'last_used_at',
        'expired_at',
    ];

    protected $hidden = [
        'api_secret_hash',
        'api_secret_encrypted',
    ];

    protected function casts(): array
    {
        return [
            'permissions' => 'array',
            'ip_whitelist' => 'array',
            'api_secret_encrypted' => 'encrypted',
            'last_used_at' => 'datetime',
            'expired_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(UserSubscription::class, 'user_subscription_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(ApiLog::class);
    }

    public function matchesSecret(string $secret): bool
    {
        return Hash::check($secret, (string) $this->api_secret_hash);
    }

    public function allowsPermission(string $permission): bool
    {
        return in_array($permission, $this->permissions ?? [], true);
    }

    public function allowsIp(?string $ipAddress): bool
    {
        $ipWhitelist = array_values(array_filter($this->ip_whitelist ?? [], static fn (mixed $value): bool => is_string($value) && trim($value) !== ''));

        if ($ipWhitelist === [] || in_array('*', $ipWhitelist, true)) {
            return true;
        }

        if ($ipAddress === null || $ipAddress === '') {
            return false;
        }

        return in_array(trim($ipAddress), $ipWhitelist, true);
    }

    public function isActive(): bool
    {
        if ($this->status !== self::STATUS_ACTIVE) {
            return false;
        }

        return $this->expired_at === null || $this->expired_at->isFuture();
    }

    public function isWalletKey(): bool
    {
        return $this->key_type === self::TYPE_WALLET;
    }

    public function isPackageKey(): bool
    {
        return $this->key_type === self::TYPE_PACKAGE;
    }

    public function markExpiredIfNeeded(): void
    {
        if ($this->expired_at === null || ! $this->expired_at->isPast() || $this->status === self::STATUS_EXPIRED) {
            return;
        }

        $this->forceFill([
            'status' => self::STATUS_EXPIRED,
        ])->saveQuietly();
    }

    /**
     * @return array{api_key:string, api_secret:string}
     */
    public static function generateCredentials(): array
    {
        return [
            'api_key' => 'ak_'.Str::lower(Str::random(28)),
            'api_secret' => 'sk_'.Str::random(40),
        ];
    }
}
