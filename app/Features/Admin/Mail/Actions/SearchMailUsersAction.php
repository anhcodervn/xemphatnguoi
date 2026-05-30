<?php

namespace App\Features\Admin\Mail\Actions;

use App\Features\Admin\Mail\Services\AdminMailService;

class SearchMailUsersAction
{
    public function __construct(
        private readonly AdminMailService $adminMailService,
    ) {
    }

    /**
     * @param array{search?:string,per_page?:int} $filters
     * @return array{
     *     data:array<int,array<string,mixed>>,
     *     meta:array<string,int>
     * }
     */
    public function handle(array $filters): array
    {
        return $this->adminMailService->searchUsers($filters);
    }
}
