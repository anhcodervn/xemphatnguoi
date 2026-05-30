<?php

namespace App\Features\Admin\User\Actions;

use App\Features\Admin\User\Services\AdminUserService;
use App\Models\User;

class ListUserWebhooksAction
{
    public function __construct(
        private readonly AdminUserService $adminUserService,
    ) {
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function handle(User $user, array $filters = []): array
    {
        return $this->adminUserService->paginateUserWebhooks($user, $filters);
    }
}
