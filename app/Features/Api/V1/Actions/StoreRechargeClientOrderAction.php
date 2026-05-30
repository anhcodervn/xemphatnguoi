<?php

namespace App\Features\Api\V1\Actions;

use App\Exceptions\ApiException;
use App\Models\ApiKey;
use App\Models\BankAccount;
use App\Models\RechargeClient;
use App\Models\RechargeMethod;
use App\Models\User;
use Illuminate\Support\Str;

class StoreRechargeClientOrderAction
{
    /**
     * @param  array{bank_id:int,amount:int|float,client_order_code?:string|null}  $payload
     */
    public function handle(User $user, ?ApiKey $apiKey, array $payload): RechargeClient
    {
        $bankAccount = BankAccount::query()
            ->where('status', 'active')
            ->find($payload['bank_id']);

        if (! $bankAccount instanceof BankAccount) {
            throw new ApiException('Tài khoản ngân hàng không hợp lệ hoặc đã ngừng hoạt động.', 422);
        }

        if (filled($payload['client_order_code'] ?? null)) {
            $exists = RechargeClient::query()
                ->whereBelongsTo($user)
                ->where('client_order_code', (string) $payload['client_order_code'])
                ->exists();

            if ($exists) {
                throw new ApiException('Mã đơn hàng phía đối tác đã tồn tại.', 422);
            }
        }

        $rechargeMethod = $bankAccount->rechargeMethods()
            ->where('recharge_methods.is_active', true)
            ->wherePivot('is_active', true)
            ->first();

        return RechargeClient::query()->create([
            'user_id' => $user->id,
            'api_key_id' => $apiKey?->id,
            'recharge_method_id' => $rechargeMethod?->id,
            'bank_account_id' => $bankAccount->id,
            'order_code' => $this->generateOrderCode(),
            'client_order_code' => $payload['client_order_code'] ?? null,
            'method' => strtolower((string) $bankAccount->bank_name),
            'method_label' => $rechargeMethod?->name ?? 'Chuyển khoản ngân hàng',
            'amount' => round((float) $payload['amount'], 2),
            'bank_name' => $bankAccount->bank_name,
            'account_number' => $bankAccount->account_number,
            'account_name' => $bankAccount->account_name,
            'transfer_content' => $this->generateTransferContent(),
            'status' => RechargeClient::STATUS_PENDING,
            'requested_at' => now(),
            'expires_at' => now()->addMinutes(60),
            'metadata' => [
                'source' => 'api.v1',
                'bank_id' => $bankAccount->id,
            ],
        ]);
    }

    private function generateOrderCode(): string
    {
        do {
            $code = 'RCL'.now()->format('ymdHis').Str::upper(Str::random(4));
        } while (RechargeClient::query()->where('order_code', $code)->exists());

        return $code;
    }

    private function generateTransferContent(): string
    {
        do {
            $content = 'NAP'.Str::upper(Str::random(8));
        } while (RechargeClient::query()->where('transfer_content', $content)->exists());

        return $content;
    }
}
