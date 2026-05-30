<?php

namespace App\Features\Client\Recharge\Services;

use App\Features\Client\Recharge\Resources\RechargeOrderResource;
use App\Features\Client\Wallet\Services\WalletService;
use App\Models\RechargeOrder;
use App\Models\User;
use App\Support\RechargeMethodCatalog;
use App\Support\SettingStore;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class RechargeService
{
    public function __construct(
        protected WalletService $walletService,
        protected RechargeMethodCatalog $rechargeMethodCatalog,
        protected SettingStore $settingStore,
    ) {
    }

    /**
     * @param array{search?:string,status?:string,per_page?:int} $filters
     * @return array<string, mixed>
     */
    public function overview(User $user, array $filters = []): array
    {
        $history = $this->history($user, $filters);

        $methods = $this->methods();
        $defaultMethod = $methods->first();

        return [
            'wallet' => $this->walletService->getWalletInfo($user),
            'bonus_percentage' => (int) ($defaultMethod['bonus_percentage'] ?? config('recharge.bonus_percentage', 0)),
            'minimum_amount' => (int) ($defaultMethod['minimum_amount'] ?? config('recharge.minimum_amount', 50_000)),
            'maximum_amount' => (int) ($defaultMethod['maximum_amount'] ?? config('recharge.maximum_amount', 100_000_000)),
            'recharge_syntax' => $this->rechargeSyntax(),
            'transfer_content_preview' => $this->buildTransferContentPreview(),
            'methods' => $methods,
            'stats' => $this->stats($user),
            'history' => [
                'data' => RechargeOrderResource::collection($history->getCollection())->resolve(),
                'meta' => [
                    'current_page' => $history->currentPage(),
                    'last_page' => $history->lastPage(),
                    'per_page' => $history->perPage(),
                    'total' => $history->total(),
                ],
            ],
        ];
    }

    /**
     * @param array{search?:string,status?:string,per_page?:int} $filters
     */
    public function history(User $user, array $filters = []): LengthAwarePaginator
    {
        $this->expireStaleOrders($user);

        $perPage = max(1, min((int) ($filters['per_page'] ?? config('recharge.default_per_page', 10)), 50));
        $search = trim((string) ($filters['search'] ?? ''));
        $status = (string) ($filters['status'] ?? 'all');

        return RechargeOrder::query()
            ->whereBelongsTo($user)
            ->when($search !== '', fn (Builder $query) => $query->where('order_code', 'like', "%{$search}%"))
            ->when($status !== '' && $status !== 'all', fn (Builder $query) => $query->where('status', $status))
            ->latest('id')
            ->paginate($perPage);
    }

    protected function expireStaleOrders(User $user): void
    {
        RechargeOrder::query()
            ->whereBelongsTo($user)
            ->whereIn('status', [
                RechargeOrder::STATUS_PENDING,
                RechargeOrder::STATUS_PROCESSING,
            ])
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->update([
                'status' => RechargeOrder::STATUS_EXPIRED,
                'updated_at' => now(),
            ]);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function methods(): Collection
    {
        return $this->rechargeMethodCatalog->methods();
    }

    public function buildTransferContentPreview(): string
    {
        return 'Mã giao dịch được tạo tự động sau khi tạo yêu cầu';
    }

    protected function rechargeSyntax(): string
    {
        return trim($this->settingStore->getString('recharge_syntax', 'NAP')) ?: 'NAP';
    }

    /**
     * @return array<string, string|int>
     */
    protected function stats(User $user): array
    {
        $paidOrders = RechargeOrder::query()
            ->whereBelongsTo($user)
            ->where('status', RechargeOrder::STATUS_PAID);

        $totalAmount = (float) (clone $paidOrders)->sum('amount');
        $totalBonus = (float) (clone $paidOrders)->sum('bonus_amount');
        $totalCount = (clone $paidOrders)->count();

        return [
            'total_recharge' => (string) $totalAmount,
            'total_bonus' => (string) $totalBonus,
            'total_orders' => $totalCount,
        ];
    }
}
