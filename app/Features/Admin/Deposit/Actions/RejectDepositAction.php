<?php

namespace App\Features\Admin\Deposit\Actions;

use App\Exceptions\ApiException;
use App\Jobs\SaveUserLogJob;
use App\Models\RechargeOrder;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RejectDepositAction
{
    /**
     * @param  array{reason?:string}  $payload
     * @return array<string, mixed>
     */
    public function handle(RechargeOrder $deposit, array $payload, User $admin): array
    {
        return DB::transaction(function () use ($deposit, $payload, $admin): array {
            $lockedDeposit = RechargeOrder::query()
                ->whereKey($deposit->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! in_array($lockedDeposit->status, [RechargeOrder::STATUS_PENDING, RechargeOrder::STATUS_PROCESSING], true)) {
                throw new ApiException('Yêu cầu nạp tiền này đã được xử lý.', 422);
            }

            $metadata = is_array($lockedDeposit->metadata) ? $lockedDeposit->metadata : [];
            $metadata['rejected_by'] = $admin->id;
            $metadata['rejected_at'] = now()->toISOString();
            $metadata['rejected_reason'] = $payload['reason'] ?? null;

            $lockedDeposit->forceFill([
                'status' => RechargeOrder::STATUS_FAILED,
                'metadata' => $metadata,
            ])->save();

            SaveUserLogJob::dispatch(
                userId: (int) $lockedDeposit->user_id,
                action: 'recharge_order_rejected',
                description: sprintf('Yêu cầu nạp %s bị từ chối', $lockedDeposit->order_code),
                ip: null,
                userAgent: 'system:admin-reject-deposit',
            )->onQueue('user-logs')->afterCommit();

            return [
                'deposit' => $lockedDeposit->fresh(['user', 'rechargeMethod', 'bankAccount']),
            ];
        });
    }
}
