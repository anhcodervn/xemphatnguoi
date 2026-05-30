<?php

namespace App\Policies;

use App\Models\User;
use App\Models\UserSubscription;

class UserSubscriptionPolicy
{
    public function view(User $user, UserSubscription $userSubscription): bool
    {
        return $user->id === $userSubscription->user_id;
    }

    public function manage(User $user, UserSubscription $userSubscription): bool
    {
        return $user->id === $userSubscription->user_id;
    }
}
