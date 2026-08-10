<?php

namespace App\Features\Admin\Couponts\Services;

use App\Models\Coupon;
use App\Models\CouponLog;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class CouponService
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        $search = trim((string) ($filters['search'] ?? ''));
        $type = (string) ($filters['type'] ?? '');
        $availability = (string) ($filters['availability'] ?? 'all');
        $perPage = max(1, min((int) ($filters['per_page'] ?? 10), 100));

        return Coupon::query()
            ->withCount('logs')
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $couponQuery) use ($search): void {
                    $couponQuery
                        ->where('code', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($type !== '', fn (Builder $query) => $query->where('type', $type))
            ->when($availability === 'active', function (Builder $query): void {
                $query
                    ->where('is_active', true)
                    ->where(function (Builder $dateQuery): void {
                        $dateQuery->whereNull('starts_at')->orWhere('starts_at', '<=', now());
                    })
                    ->where(function (Builder $dateQuery): void {
                        $dateQuery->whereNull('expired_at')->orWhere('expired_at', '>', now());
                    });
            })
            ->when($availability === 'inactive', fn (Builder $query) => $query->where('is_active', false))
            ->when($availability === 'scheduled', fn (Builder $query) => $query->whereNotNull('starts_at')->where('starts_at', '>', now()))
            ->when($availability === 'expired', fn (Builder $query) => $query->whereNotNull('expired_at')->where('expired_at', '<=', now()))
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @return array<string, int>
     */
    public function summary(): array
    {
        return [
            'total' => Coupon::query()->count(),
            'active' => Coupon::query()
                ->where('is_active', true)
                ->where(function (Builder $query): void {
                    $query->whereNull('starts_at')->orWhere('starts_at', '<=', now());
                })
                ->where(function (Builder $query): void {
                    $query->whereNull('expired_at')->orWhere('expired_at', '>', now());
                })
                ->count(),
            'scheduled' => Coupon::query()->whereNotNull('starts_at')->where('starts_at', '>', now())->count(),
            'expired' => Coupon::query()->whereNotNull('expired_at')->where('expired_at', '<=', now())->count(),
            'total_used' => (int) Coupon::query()->sum('used_count'),
        ];
    }

    public function show(Coupon $coupon): Coupon
    {
        return $coupon->load([
            'logs' => fn (HasMany $query) => $query->with([
                'user:id,full_name,username,email',
                'admin:id,full_name,username,email',
            ])->latest('id')->limit(10),
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function create(array $payload, User $admin): Coupon
    {
        return DB::transaction(function () use ($payload, $admin): Coupon {
            $coupon = Coupon::query()->create($this->normalizePayload($payload));

            $this->recordLog(
                coupon: $coupon,
                admin: $admin,
                action: 'created',
                status: 'success',
                note: 'Tạo coupon mới',
                payload: $coupon->toArray(),
            );

            return $coupon->fresh();
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function update(Coupon $coupon, array $payload, User $admin): Coupon
    {
        return DB::transaction(function () use ($coupon, $payload, $admin): Coupon {
            $before = $coupon->toArray();

            $coupon->update($this->normalizePayload($payload));

            $this->recordLog(
                coupon: $coupon,
                admin: $admin,
                action: 'updated',
                status: 'success',
                note: 'Cập nhật coupon',
                payload: [
                    'before' => $before,
                    'after' => $coupon->fresh()->toArray(),
                ],
            );

            return $coupon->fresh();
        });
    }

    public function delete(Coupon $coupon, User $admin): void
    {
        DB::transaction(function () use ($coupon, $admin): void {
            $this->recordLog(
                coupon: $coupon,
                admin: $admin,
                action: 'deleted',
                status: 'info',
                note: 'Xóa coupon',
                payload: $coupon->toArray(),
            );

            $coupon->delete();
        });
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginateLogs(array $filters = [], ?Coupon $coupon = null): LengthAwarePaginator
    {
        $search = trim((string) ($filters['search'] ?? ''));
        $status = (string) ($filters['status'] ?? '');
        $action = trim((string) ($filters['action'] ?? ''));
        $couponId = (int) ($filters['coupon_id'] ?? 0);
        $userId = (int) ($filters['user_id'] ?? 0);
        $perPage = max(1, min((int) ($filters['per_page'] ?? 10), 100));

        return CouponLog::query()
            ->with([
                'coupon:id,code,name',
                'user:id,full_name,username,email',
                'admin:id,full_name,username,email',
            ])
            ->when($coupon !== null, fn (Builder $query) => $query->where('coupon_id', $coupon->id))
            ->when($coupon === null && $couponId > 0, fn (Builder $query) => $query->where('coupon_id', $couponId))
            ->when($userId > 0, fn (Builder $query) => $query->where('user_id', $userId))
            ->when($status !== '', fn (Builder $query) => $query->where('status', $status))
            ->when($action !== '', fn (Builder $query) => $query->where('action', $action))
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $logQuery) use ($search): void {
                    $logQuery
                        ->where('note', 'like', "%{$search}%")
                        ->orWhereHas('coupon', fn (Builder $couponQuery) => $couponQuery->where('code', 'like', "%{$search}%"))
                        ->orWhereHas('user', fn (Builder $userQuery) => $userQuery->where('email', 'like', "%{$search}%")->orWhere('name', 'like', "%{$search}%"));
                });
            })
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    protected function normalizePayload(array $payload): array
    {
        return [
            'code' => strtoupper(trim((string) $payload['code'])),
            'name' => trim((string) $payload['name']),
            'description' => filled($payload['description'] ?? null) ? trim((string) $payload['description']) : null,
            'type' => $payload['type'],
            'value' => $payload['value'],
            'min_order_amount' => $payload['min_order_amount'] ?? 0,
            'max_discount_amount' => $payload['max_discount_amount'] ?? null,
            'max_usage' => $payload['max_usage'] ?? null,
            'max_usage_per_user' => $payload['max_usage_per_user'] ?? null,
            'starts_at' => $payload['starts_at'] ?? null,
            'expired_at' => $payload['expired_at'] ?? null,
            'first_order_only' => (bool) ($payload['first_order_only'] ?? false),
            'is_active' => (bool) ($payload['is_active'] ?? true),
            'requirements' => $payload['requirements'] ?? [],
        ];
    }

    /**
     * @param  array<string, mixed>|null  $payload
     */
    public function recordLog(
        Coupon $coupon,
        ?User $admin,
        string $action,
        string $status,
        ?string $note = null,
        ?User $user = null,
        ?float $orderAmount = null,
        ?float $discountAmount = null,
        ?array $payload = null,
    ): CouponLog {
        return $coupon->logs()->create([
            'user_id' => $user?->id,
            'admin_id' => $admin?->id,
            'action' => $action,
            'status' => $status,
            'order_amount' => $orderAmount,
            'discount_amount' => $discountAmount,
            'note' => $note,
            'payload' => $payload,
        ]);
    }
}
