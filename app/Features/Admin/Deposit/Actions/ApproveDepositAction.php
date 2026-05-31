<?php

namespace App\Features\Admin\Deposit\Actions;

use App\Exceptions\ApiException;
use App\Jobs\SaveUserLogJob;
use App\Models\RechargeOrder;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Support\MailQueue;
use Illuminate\Support\Facades\DB;

class ApproveDepositAction
{
    public function __construct(
        private readonly MailQueue $mailQueue,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function handle(RechargeOrder $deposit, User $admin): array
    {
        return DB::transaction(function () use ($deposit, $admin): array {
            $lockedDeposit = RechargeOrder::query()
                ->with('user')
                ->whereKey($deposit->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! in_array($lockedDeposit->status, [RechargeOrder::STATUS_PENDING, RechargeOrder::STATUS_PROCESSING], true)) {
                throw new ApiException('Yêu cầu nạp tiền này đã được xử lý.', 422);
            }

            $wallet = Wallet::query()
                ->where('user_id', $lockedDeposit->user_id)
                ->where('type', Wallet::TYPE_MAIN)
                ->lockForUpdate()
                ->first();

            if (! $wallet instanceof Wallet) {
                $wallet = $lockedDeposit->user->wallets()->create([
                    'type' => Wallet::TYPE_MAIN,
                    'balance' => 0,
                    'hold_balance' => 0,
                    'total_recharge' => 0,
                    'total_spent' => 0,
                ]);
                $wallet = Wallet::query()->whereKey($wallet->id)->lockForUpdate()->firstOrFail();
            }

            $creditAmount = (float) $lockedDeposit->total_amount;
            $balanceBefore = (float) $wallet->balance;
            $balanceAfter = $balanceBefore + $creditAmount;

            $wallet->forceFill([
                'balance' => $balanceAfter,
                'total_recharge' => (float) $wallet->total_recharge + (float) $lockedDeposit->amount,
            ])->save();

            $walletTransaction = WalletTransaction::query()->create([
                'wallet_id' => $wallet->id,
                'type' => 'credit',
                'amount' => $creditAmount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'reference_type' => RechargeOrder::class,
                'reference_id' => $lockedDeposit->id,
                'description' => 'Admin approved deposit '.$lockedDeposit->order_code,
                'status' => 'success',
            ]);

            $metadata = is_array($lockedDeposit->metadata) ? $lockedDeposit->metadata : [];
            $metadata['approved_by'] = $admin->id;
            $metadata['approved_at'] = now()->toISOString();

            $lockedDeposit->forceFill([
                'status' => RechargeOrder::STATUS_PAID,
                'paid_at' => $lockedDeposit->paid_at ?? now(),
                'metadata' => $metadata,
            ])->save();

            SaveUserLogJob::dispatch(
                userId: (int) $lockedDeposit->user_id,
                action: 'recharge_order_paid',
                description: sprintf('Yêu cầu nạp %s đã được duyệt thành công', $lockedDeposit->order_code),
                ip: null,
                userAgent: 'system:admin-approve-deposit',
            )->onQueue('user-logs')->afterCommit();

            $userEmail = $lockedDeposit->user?->email;
            if (is_string($userEmail) && $userEmail !== '') {
                $this->mailQueue->dispatch(
                    to: $userEmail,
                    subjectText: 'Nạp tiền thành công',
                    title: 'Yêu cầu nạp tiền đã được duyệt',
                    messageLines: [
                        sprintf('Mã giao dịch: %s', $lockedDeposit->order_code),
                        sprintf('Số tiền nhận: %s', number_format((float) $lockedDeposit->total_amount, 0, ',', '.').'đ'),
                    ],
                );
            }

            return [
                'deposit' => $lockedDeposit->fresh(['user', 'rechargeMethod', 'bankAccount']),
                'wallet_transaction' => $walletTransaction->fresh(),
            ];
        });
    }
}
