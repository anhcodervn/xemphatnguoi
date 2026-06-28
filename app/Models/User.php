<?php

namespace App\Models;

use App\Notifications\QueuedResetPasswordNotification;
use Illuminate\Auth\Passwords\CanResetPassword as CanResetPasswordTrait;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements CanResetPassword, JWTSubject
{
    use CanResetPasswordTrait, HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'username',
        'email',
        'phone',
        'full_name',
        'avatar',
        'google_id',
        'password',
        'role',
        'status',
        'email_verified_at',
        'last_login_at',
        'last_login_ip',
        'referral_code',
        'referred_by',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $appends = [
        'name',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'deleted_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $user): void {
            if (blank($user->username)) {
                $user->username = $user->generateUniqueUsername();
            }
        });

        static::created(function (self $user): void {
            $user->wallets()->firstOrCreate([
                'type' => Wallet::TYPE_MAIN,
            ], [
                'balance' => 0,
                'hold_balance' => 0,
                'total_recharge' => 0,
                'total_spent' => 0,
            ]);
        });
    }

    public function getNameAttribute(): string
    {
        return $this->full_name ?: $this->username;
    }

    public function setNameAttribute(?string $value): void
    {
        $this->attributes['full_name'] = $value;
    }

    public function referrer(): BelongsTo
    {
        return $this->belongsTo(self::class, 'referred_by');
    }

    public function referrals(): HasMany
    {
        return $this->hasMany(self::class, 'referred_by');
    }

    public function cronJobs(): HasMany
    {
        return $this->hasMany(CronJob::class);
    }

    public function cronJobLogs(): HasMany
    {
        return $this->hasMany(CronJobLog::class);
    }

    public function cronAlertChannels(): HasMany
    {
        return $this->hasMany(CronAlertChannel::class);
    }

    public function cronUsageCounters(): HasMany
    {
        return $this->hasMany(CronUsageCounter::class);
    }

    public function userSessions(): HasMany
    {
        return $this->hasMany(UserSession::class);
    }

    public function apiKeys(): HasMany
    {
        return $this->hasMany(ApiKey::class);
    }

    public function wallet(): HasOne
    {
        return $this->hasOne(Wallet::class)->where('type', Wallet::TYPE_MAIN);
    }

    public function bankAccounts(): HasMany
    {
        return $this->hasMany(BankAccount::class);
    }

    public function wallets(): HasMany
    {
        return $this->hasMany(Wallet::class);
    }

    public function userPackages(): HasMany
    {
        return $this->hasMany(UserPackage::class);
    }

    public function packageOrders(): HasMany
    {
        return $this->hasMany(PackageOrder::class);
    }

    public function userSubscriptions(): HasMany
    {
        return $this->hasMany(UserSubscription::class);
    }

    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class);
    }

    public function paymentTransactions(): HasMany
    {
        return $this->hasMany(PaymentTransaction::class);
    }

    public function apiLogs(): HasMany
    {
        return $this->hasMany(ApiLog::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    public function notificationReads(): HasMany
    {
        return $this->hasMany(NotificationRead::class);
    }

    public function userLogs(): HasMany
    {
        return $this->hasMany(UserLog::class);
    }

    public function webhooks(): HasMany
    {
        return $this->hasMany(Webhook::class);
    }

    public function rechargeOrders(): HasMany
    {
        return $this->hasMany(RechargeOrder::class);
    }

    public function rechargeClientOrders(): HasMany
    {
        return $this->hasMany(RechargeClient::class);
    }

    public function couponLogs(): HasMany
    {
        return $this->hasMany(CouponLog::class);
    }

    public function getJWTIdentifier(): mixed
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims(): array
    {
        return [];
    }

    protected function generateUniqueUsername(): string
    {
        $seed = $this->username
            ?: $this->email
            ?: $this->full_name
            ?: Str::random(8);

        $username = Str::of($seed)
            ->before('@')
            ->lower()
            ->slug('')
            ->value();

        $username = $username !== '' ? Str::limit($username, 32, '') : Str::lower(Str::random(8));
        $original = $username;
        $counter = 1;

        while (static::withTrashed()->where('username', $username)->exists()) {
            $suffix = (string) $counter;
            $username = Str::limit($original, max(1, 32 - strlen($suffix)), '').$suffix;
            $counter++;
        }

        return $username;
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new QueuedResetPasswordNotification((string) $token));
    }
}
