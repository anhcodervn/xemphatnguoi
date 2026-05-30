<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class ApiKey extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_REVOKED = 'revoked';

    protected $fillable = [
        'user_id',
        'name',
        'api_key',
        'api_secret',
        'permissions',
        'ip_whitelist',
        'last_used_at',
        'expired_at',
        'status',
    ];

    protected $hidden = [
        'api_secret',
    ];

    protected function casts(): array
    {
        return [
            'permissions' => 'array',
            'ip_whitelist' => 'array',
            'last_used_at' => 'datetime',
            'expired_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function apiLogs(): HasMany
    {
        return $this->hasMany(ApiLog::class);
    }

    public function rechargeClientOrders(): HasMany
    {
        return $this->hasMany(RechargeClient::class);
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE && ! $this->isExpired();
    }

    public function isExpired(): bool
    {
        return $this->expired_at !== null && $this->expired_at->isPast();
    }

    public function markExpiredIfNeeded(): void
    {
        if (! $this->isExpired() || $this->status === self::STATUS_EXPIRED) {
            return;
        }

        $this->forceFill([
            'status' => self::STATUS_EXPIRED,
        ])->saveQuietly();
    }

    public function matchesSecret(string $secret): bool
    {
        if ($secret === '') {
            return false;
        }

        if (($info = password_get_info($this->api_secret))['algo'] !== null && password_verify($secret, $this->api_secret)) {
            return true;
        }

        return hash_equals($this->api_secret, $secret);
    }

    public function allowsPermission(string $permission): bool
    {
        $permissions = $this->permissions ?? [];

        if ($permissions === [] || $permission === '') {
            return false;
        }

        foreach ($permissions as $grantedPermission) {
            if (! is_string($grantedPermission) || $grantedPermission === '') {
                continue;
            }

            if ($grantedPermission === '*' || $grantedPermission === $permission || Str::is($grantedPermission, $permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<int, string>|null  $whitelist
     */
    public function allowsIp(?string $ip, ?array $whitelist = null): bool
    {
        $ipWhitelist = $whitelist ?? $this->ip_whitelist ?? [];

        if ($ipWhitelist === []) {
            return true;
        }

        if (in_array('*', $ipWhitelist, true)) {
            return true;
        }

        if ($ip === null || $ip === '') {
            return false;
        }

        return in_array($ip, $ipWhitelist, true);
    }
}
