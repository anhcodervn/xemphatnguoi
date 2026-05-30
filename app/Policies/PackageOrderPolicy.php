<?php

namespace App\Policies;

use App\Models\PackageOrder;
use App\Models\User;

class PackageOrderPolicy
{
    public function view(User $user, PackageOrder $packageOrder): bool
    {
        return $user->id === $packageOrder->user_id;
    }

    public function manage(User $user, PackageOrder $packageOrder): bool
    {
        return $user->id === $packageOrder->user_id;
    }
}
