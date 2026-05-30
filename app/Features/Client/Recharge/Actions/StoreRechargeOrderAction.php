<?php

namespace App\Features\Client\Recharge\Actions;

use App\Models\RechargeOrder;
use App\Models\User;
use App\Support\RechargeMethodCatalog;
use Illuminate\Support\Str;

class StoreRechargeOrderAction
{
    public function __construct(
        protected RechargeMethodCatalog $rechargeMethodCatalog,
    ) {
    }

    /**
     * @param array{method:string,amount:int|float} $payload
     */
    public function handle(User $user, array $payload): RechargeOrder
    {
        $methodConfig = $this->rechargeMethodCatalog->find($payload['method']);

        if ($methodConfig === null) {
            abort(422, 'Phương thức nạp không khả dụng.');
        }

        $amount = round((float) $payload['amount'], 2);
        $bonusAmount = round($amount * ((int) ($methodConfig['bonus_percentage'] ?? 0) / 100), 2);

        $orderCode = $this->generateOrderCode();

        return $user->rechargeOrders()->create([
            'recharge_method_id' => $methodConfig['recharge_method_id'] ?? null,
            'bank_account_id' => $methodConfig['bank_account_id'] ?? null,
            'order_code' => $orderCode,
            'method' => $payload['method'],
            'method_label' => (string) $methodConfig['label'],
            'amount' => $amount,
            'bonus_amount' => $bonusAmount,
            'total_amount' => $amount + $bonusAmount,
            'bank_name' => $methodConfig['bank_name'] ?? null,
            'account_number' => $methodConfig['account_number'] ?? null,
            'account_name' => $methodConfig['account_name'] ?? null,
            'transfer_content' => $orderCode,
            'status' => RechargeOrder::STATUS_PENDING,
            'requested_at' => now(),
            'expires_at' => now()->addMinutes(60),
            'metadata' => [
                'description' => $methodConfig['description'],
                'badge_label' => $methodConfig['badge_label'],
                'badge_type' => $methodConfig['badge_type'],
                'source' => $methodConfig['source'],
            ],
        ]);
    }

    protected function generateOrderCode(): string
    {
        return 'DEP'.now()->format('ymdHis').Str::upper(Str::random(4));
    }
}
