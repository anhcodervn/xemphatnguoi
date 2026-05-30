<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RechargeMethod extends Model
{
    /** @use HasFactory<\Database\Factories\RechargeMethodFactory> */
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'description',
        'badge_label',
        'badge_type',
        'bank_name',
        'account_number',
        'account_name',
        'min_amount',
        'max_amount',
        'bonus_percentage',
        'sort_order',
        'is_active',
        'metadata',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'min_amount' => 'decimal:2',
            'max_amount' => 'decimal:2',
            'bonus_percentage' => 'integer',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
            'metadata' => 'array',
        ];
    }

    public function bankAccounts(): BelongsToMany
    {
        return $this->belongsToMany(BankAccount::class, 'recharge_method_bank_account')
            ->withPivot(['sort_order', 'is_active'])
            ->withTimestamps()
            ->orderByPivot('sort_order')
            ->orderBy('bank_accounts.id');
    }

    public function rechargeOrders(): HasMany
    {
        return $this->hasMany(RechargeOrder::class);
    }
}
