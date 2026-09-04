<?php

namespace App\Features\Client\MonitoringPlan\Actions;

use App\Exceptions\ApiException;
use App\Models\MonitoringSubscription;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class UpdateMonitoringAutoRenewAction
{
    public function handle(User $user, bool $enabled): MonitoringSubscription
    {
        return DB::transaction(function () use ($user, $enabled): MonitoringSubscription {
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->id);
            $subscription = $lockedUser->monitoringSubscriptions()
                ->where('status', MonitoringSubscription::STATUS_ACTIVE)
                ->where(function (Builder $query): void {
                    $query->where('expires_at', '>', now())->orWhere('auto_renew', true);
                })
                ->latest('expires_at')
                ->lockForUpdate()
                ->first();

            if (! $subscription instanceof MonitoringSubscription) {
                throw new ApiException('Không tìm thấy gói dịch vụ có thể cấu hình tự gia hạn.', 422);
            }

            if ($enabled && ! $subscription->plan()->where('is_active', true)->exists()) {
                throw new ApiException('Gói dịch vụ đã ngừng bán nên không thể bật tự gia hạn.', 422);
            }

            $subscription->update(['auto_renew' => $enabled]);

            return $subscription->refresh();
        });
    }
}
