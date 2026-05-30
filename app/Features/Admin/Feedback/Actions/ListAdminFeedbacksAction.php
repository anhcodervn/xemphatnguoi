<?php

namespace App\Features\Admin\Feedback\Actions;

use App\Features\Admin\Feedback\Services\AdminFeedbackService;

class ListAdminFeedbacksAction
{
    public function __construct(private readonly AdminFeedbackService $adminFeedbackService)
    {
    }

    /**
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    public function handle(array $filters = []): array
    {
        return $this->adminFeedbackService->paginate($filters);
    }
}
