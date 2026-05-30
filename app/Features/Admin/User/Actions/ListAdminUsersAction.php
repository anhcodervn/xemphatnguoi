<?php

namespace App\Features\Admin\User\Actions;

use App\Features\Admin\User\Services\AdminUserService;

class ListAdminUsersAction
{
    public function __construct(
        private readonly AdminUserService $adminUserService,
    ) {
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function handle(array $filters = []): array
    {
        return $this->adminUserService->paginateUsers($filters);
    }
}
