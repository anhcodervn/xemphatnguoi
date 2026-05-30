<?php

namespace App\Features\Admin\Mail\Services;

use App\Models\User;
use App\Support\MailQueue;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class AdminMailService
{
    public function __construct(
        private readonly MailQueue $mailQueue,
    ) {
    }

    /**
     * @param array{search?:string,per_page?:int} $filters
     * @return array{
     *     data:array<int,array<string,mixed>>,
     *     meta:array<string,int>
     * }
     */
    public function searchUsers(array $filters): array
    {
        $search = trim((string) ($filters['search'] ?? ''));
        $perPage = max(1, min((int) ($filters['per_page'] ?? 10), 50));

        $users = User::query()
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $subQuery) use ($search): void {
                    $subQuery
                        ->where('id', is_numeric($search) ? (int) $search : -1)
                        ->orWhere('username', 'like', "%{$search}%")
                        ->orWhere('full_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->whereNotNull('email')
            ->latest('id')
            ->paginate($perPage);

        return [
            'data' => $users->getCollection()
                ->map(fn (User $user): array => [
                    'id' => $user->id,
                    'username' => $user->username,
                    'full_name' => $user->full_name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'status' => $user->status,
                ])
                ->values()
                ->all(),
            'meta' => $this->paginationMeta($users),
        ];
    }

    /**
     * @param array{
     *   recipient_type:string,
     *   user_ids?:array<int,int>,
     *   subject:string,
     *   title:string,
     *   message:string,
     *   cta_text?:?string,
     *   cta_url?:?string
     * } $payload
     * @return array{queued:int,skipped:int}
     */
    public function sendToUsers(array $payload): array
    {
        $recipientType = (string) $payload['recipient_type'];

        $query = User::query()->whereNotNull('email');
        if ($recipientType === 'users') {
            $userIds = collect((array) ($payload['user_ids'] ?? []))
                ->map(fn (mixed $id): int => (int) $id)
                ->filter(fn (int $id): bool => $id > 0)
                ->unique()
                ->values()
                ->all();

            $query->whereIn('id', $userIds);
        }

        $users = $query->get(['id', 'email']);
        $queued = 0;
        $skipped = 0;

        foreach ($users as $user) {
            if (! is_string($user->email) || trim($user->email) === '') {
                $skipped++;
                continue;
            }

            $this->mailQueue->dispatch(
                to: $user->email,
                subjectText: $payload['subject'],
                title: $payload['title'],
                messageLines: preg_split('/\r\n|\r|\n/', $payload['message']) ?: [$payload['message']],
                ctaText: $payload['cta_text'] ?? null,
                ctaUrl: $payload['cta_url'] ?? null,
            );
            $queued++;
        }

        return [
            'queued' => $queued,
            'skipped' => $skipped,
        ];
    }

    /**
     * @return array{current_page:int,last_page:int,per_page:int,total:int}
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
