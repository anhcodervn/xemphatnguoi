<?php

namespace App\Policies;

use App\Models\ExtraAccountOrder;
use App\Models\User;

class ExtraAccountOrderPolicy
{
    public function view(User $user, ExtraAccountOrder $extraAccountOrder): bool
    {
        return $user->id === $extraAccountOrder->subscription->user_id;
    }

    public function manage(User $user, ExtraAccountOrder $extraAccountOrder): bool
    {
        return $user->id === $extraAccountOrder->subscription->user_id;
    }
}
