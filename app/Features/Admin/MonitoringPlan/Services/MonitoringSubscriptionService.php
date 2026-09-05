<?php

namespace App\Features\Admin\MonitoringPlan\Services;

use App\Models\MonitoringSubscription;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class MonitoringSubscriptionService
{
    /** @param array<string, mixed> $filters */
    public function paginate(array $filters): LengthAwarePaginator
    {
        $search = trim((string) ($filters['search'] ?? ''));
        $status = (string) ($filters['status'] ?? '');

        return MonitoringSubscription::query()
            ->with(['user:id,username,email,full_name', 'plan:id,name'])
            ->whereIn('id', MonitoringSubscription::query()
                ->selectRaw('MAX(id)')
                ->groupBy('user_id'))
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($inner) use ($search): void {
                    $inner->where('plan_name', 'like', "%{$search}%")
                        ->orWhereHas('user', fn ($userQuery) => $userQuery
                            ->where('username', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('full_name', 'like', "%{$search}%"));
                });
            })
            ->when($status === MonitoringSubscription::STATUS_ACTIVE, fn ($query) => $query->active())
            ->when($status === 'expired', fn ($query) => $query
                ->where('status', MonitoringSubscription::STATUS_ACTIVE)
                ->where('expires_at', '<=', now()))
            ->when(in_array($status, [MonitoringSubscription::STATUS_RENEWED, MonitoringSubscription::STATUS_UPGRADED], true), fn ($query) => $query->where('status', $status))
            ->when(array_key_exists('auto_renew', $filters), fn ($query) => $query->where('auto_renew', (bool) $filters['auto_renew']))
            ->latest('started_at')
            ->latest('id')
            ->paginate((int) ($filters['per_page'] ?? 20))
            ->withQueryString();
    }
}
