<?php

namespace App\Features\Admin\Notifications\Services;

use App\Features\Admin\Notifications\Resources\AdminNotificationResource;
use App\Models\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class AdminNotificationService
{
    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function paginate(array $filters = []): array
    {
        $perPage = (int) ($filters['per_page'] ?? 15);
        $notifications = $this->query($filters)->paginate($perPage)->withQueryString();

        return [
            'data' => AdminNotificationResource::collection($notifications->getCollection())->resolve(),
            'meta' => $this->paginationMeta($notifications),
            'stats' => [
                'total' => $this->baseStatsQuery($filters)->count(),
                'system' => $this->baseStatsQuery($filters)->where('scope', Notification::SCOPE_SYSTEM)->count(),
                'user' => $this->baseStatsQuery($filters)->where('scope', Notification::SCOPE_USER)->count(),
                'today' => $this->baseStatsQuery($filters)->whereDate('created_at', today())->count(),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function query(array $filters): Builder
    {
        return Notification::query()
            ->with(['user:id,username,full_name,email,phone'])
            ->withCount('reads')
            ->when(filled($filters['search'] ?? null), function (Builder $query) use ($filters): void {
                $search = trim((string) $filters['search']);
                $query->where(function (Builder $builder) use ($search): void {
                    if (is_numeric($search)) {
                        $builder->orWhere('id', (int) $search);
                    }

                    $builder
                        ->orWhere('title', 'like', "%{$search}%")
                        ->orWhere('content', 'like', "%{$search}%")
                        ->orWhereHas('user', function (Builder $userQuery) use ($search): void {
                            if (is_numeric($search)) {
                                $userQuery->orWhere('id', (int) $search);
                            }

                            $userQuery
                                ->orWhere('username', 'like', "%{$search}%")
                                ->orWhere('full_name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%")
                                ->orWhere('phone', 'like', "%{$search}%");
                        });
                });
            })
            ->when(filled($filters['scope'] ?? null), fn (Builder $query) => $query->where('scope', (string) $filters['scope']))
            ->when(filled($filters['user_id'] ?? null), fn (Builder $query) => $query->where('user_id', (int) $filters['user_id']))
            ->when(filled($filters['type'] ?? null), fn (Builder $query) => $query->where('type', (string) $filters['type']))
            ->latest('id');
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function baseStatsQuery(array $filters): Builder
    {
        return (clone $this->query($filters))->reorder();
    }

    /**
     * @return array<string, int>
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
