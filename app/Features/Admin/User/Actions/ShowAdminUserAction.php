<?php

namespace App\Features\Admin\User\Actions;

use App\Features\Admin\User\Services\AdminUserService;
use App\Models\User;

class ShowAdminUserAction
{
    public function __construct(
        private readonly AdminUserService $adminUserService,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function handle(User $user): array
    {
        return $this->adminUserService->userDetail($user);
    }
}
