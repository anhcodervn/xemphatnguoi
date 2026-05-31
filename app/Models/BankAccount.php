<?php

namespace App\Models;

use App\Utils\EncodeBank;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BankAccount extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'bank_name',
        'account_name',
        'account_number',
        'username',
        'password',
        'token',
        'data_login',
        'proxy',
        'status',
        'last_sync_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'last_sync_at' => 'datetime',
        ];
    }

    protected function password(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value): ?string => EncodeBank::decode($value),
            set: fn (?string $value): ?string => EncodeBank::encode($value),
        );
    }

    protected function username(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value): ?string => EncodeBank::decode($value),
            set: fn (?string $value): ?string => EncodeBank::encode($value),
        );
    }

    protected function token(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value): ?string => EncodeBank::decode($value),
            set: fn (?string $value): ?string => EncodeBank::encode($value),
        );
    }

    protected function proxy(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value): ?string => EncodeBank::decode($value),
            set: fn (?string $value): ?string => EncodeBank::encode($value),
        );
    }

    protected function dataLogin(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value): array => EncodeBank::decodeJson($value),
            set: fn (mixed $value): ?string => EncodeBank::encodeJson($value),
        );
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(BankTransaction::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function webhooks(): HasMany
    {
        return $this->hasMany(Webhook::class);
    }

    public function rechargeMethods(): BelongsToMany
    {
        return $this->belongsToMany(RechargeMethod::class, 'recharge_method_bank_account')
            ->withPivot(['sort_order', 'is_active'])
            ->withTimestamps()
            ->orderByPivot('sort_order')
            ->orderBy('recharge_methods.id');
    }

    public function rechargeClientOrders(): HasMany
    {
        return $this->hasMany(RechargeClient::class);
    }
}
