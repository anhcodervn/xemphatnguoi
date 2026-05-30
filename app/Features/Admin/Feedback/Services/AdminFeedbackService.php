<?php

namespace App\Features\Admin\Feedback\Services;

use App\Features\Admin\Feedback\Resources\AdminFeedbackResource;
use App\Models\ContactFeedback;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class AdminFeedbackService
{
    /**
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    public function paginate(array $filters = []): array
    {
        $perPage = (int) ($filters['per_page'] ?? 15);
        $feedbacks = $this->query($filters)->paginate($perPage)->withQueryString();

        return [
            'data' => AdminFeedbackResource::collection($feedbacks->getCollection())->resolve(),
            'meta' => $this->paginationMeta($feedbacks),
            'stats' => [
                'total' => ContactFeedback::query()->count(),
                'new' => ContactFeedback::query()->where('status', ContactFeedback::STATUS_NEW)->count(),
                'in_progress' => ContactFeedback::query()->where('status', ContactFeedback::STATUS_IN_PROGRESS)->count(),
                'done' => ContactFeedback::query()->where('status', ContactFeedback::STATUS_DONE)->count(),
            ],
        ];
    }

    public function updateStatus(ContactFeedback $feedback, User $admin, string $status): ContactFeedback
    {
        $feedback->forceFill([
            'status' => $status,
            'handled_by' => $admin->id,
            'handled_at' => now(),
        ])->save();

        return $feedback->loadMissing(['user:id,username,full_name,email,phone', 'handler:id,username,full_name']);
    }

    /**
     * @param array<string, mixed> $filters
     */
    private function query(array $filters): Builder
    {
        return ContactFeedback::query()
            ->with(['user:id,username,full_name,email,phone', 'handler:id,username,full_name'])
            ->when(filled($filters['search'] ?? null), function (Builder $query) use ($filters): void {
                $search = trim((string) $filters['search']);
                $query->where(function (Builder $builder) use ($search): void {
                    if (is_numeric($search)) {
                        $builder->orWhere('id', (int) $search)->orWhere('user_id', (int) $search);
                    }

                    $builder
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('subject', 'like', "%{$search}%")
                        ->orWhere('content', 'like', "%{$search}%");
                });
            })
            ->when(filled($filters['status'] ?? null), fn (Builder $query) => $query->where('status', (string) $filters['status']))
            ->when(filled($filters['user_id'] ?? null), fn (Builder $query) => $query->where('user_id', (int) $filters['user_id']))
            ->latest('id');
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
