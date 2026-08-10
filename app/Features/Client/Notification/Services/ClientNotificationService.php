<?php

namespace App\Features\Client\Notification\Services;

use App\Features\Client\Notification\Resources\ClientNotificationResource;
use App\Models\Notification;
use App\Models\NotificationRead;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class ClientNotificationService
{
    /**
     * @return array{unread: int, notifications: array<int, array<string, mixed>>}
     */
    public function dashboard(User $user, int $limit = 4): array
    {
        $filters = ['scope' => Notification::SCOPE_SYSTEM];
        $notifications = $this->queryForUser($user, $filters)
            ->limit($limit)
            ->get();

        $unread = $this->baseStatsQuery($user, $filters)
            ->whereDoesntHave('reads', fn (Builder $query) => $query->where('user_id', $user->id))
            ->count();

        return [
            'unread' => $unread,
            'notifications' => ClientNotificationResource::collection($notifications)->resolve(),
        ];
    }

    /**
     * @param  array<string,mixed>  $filters
     * @return array<string,mixed>
     */
    public function paginate(User $user, array $filters = []): array
    {
        $perPage = (int) ($filters['per_page'] ?? 15);
        $notifications = $this->queryForUser($user, $filters)->paginate($perPage)->withQueryString();

        return [
            'data' => ClientNotificationResource::collection($notifications->getCollection())->resolve(),
            'meta' => $this->paginationMeta($notifications),
            'stats' => [
                'total' => $this->baseStatsQuery($user, $filters)->count(),
                'unread' => $this->baseStatsQuery($user, $filters)->whereDoesntHave('reads', fn (Builder $query) => $query->where('user_id', $user->id))->count(),
            ],
        ];
    }

    public function markAsRead(User $user, Notification $notification): NotificationRead
    {
        return NotificationRead::query()->firstOrCreate(
            [
                'notification_id' => $notification->id,
                'user_id' => $user->id,
            ],
            [
                'read_at' => now(),
            ]
        );
    }

    /**
     * @param  array<string,mixed>  $filters
     */
    private function queryForUser(User $user, array $filters = []): Builder
    {
        $scope = (string) ($filters['scope'] ?? 'all');

        return Notification::query()
            ->where(function (Builder $query) use ($user, $scope): void {
                if ($scope === Notification::SCOPE_SYSTEM) {
                    $query->where('scope', Notification::SCOPE_SYSTEM);

                    return;
                }

                if ($scope === Notification::SCOPE_USER) {
                    $query->where('scope', Notification::SCOPE_USER)->where('user_id', $user->id);

                    return;
                }

                $query
                    ->where('scope', Notification::SCOPE_SYSTEM)
                    ->orWhere(function (Builder $userScope) use ($user): void {
                        $userScope
                            ->where('scope', Notification::SCOPE_USER)
                            ->where('user_id', $user->id);
                    });
            })
            ->withCount(['reads as is_read' => fn (Builder $query) => $query->where('user_id', $user->id)])
            ->when(filled($filters['search'] ?? null), function (Builder $query) use ($filters): void {
                $search = trim((string) $filters['search']);
                $query->where(function (Builder $builder) use ($search): void {
                    if (is_numeric($search)) {
                        $builder->orWhere('id', (int) $search);
                    }

                    $builder->orWhere('title', 'like', "%{$search}%")->orWhere('content', 'like', "%{$search}%");
                });
            })
            ->when(array_key_exists('is_read', $filters) && $filters['is_read'] !== null && $filters['is_read'] !== '', function (Builder $query) use ($filters, $user): void {
                $isRead = (bool) $filters['is_read'];
                if ($isRead) {
                    $query->whereHas('reads', fn (Builder $readQuery) => $readQuery->where('user_id', $user->id));
                } else {
                    $query->whereDoesntHave('reads', fn (Builder $readQuery) => $readQuery->where('user_id', $user->id));
                }
            })
            ->latest('id');
    }

    /**
     * @param  array<string,mixed>  $filters
     */
    private function baseStatsQuery(User $user, array $filters = []): Builder
    {
        return (clone $this->queryForUser($user, $filters))->reorder();
    }

    /**
     * @return array<string,int>
     */
    private function paginationMeta(LengthAwarePaginator $paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
        ];
    }
}
