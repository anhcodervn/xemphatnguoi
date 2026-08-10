<?php

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('users.{userId}.proxy-orders', function (User $user, int $userId): bool {
    return $user->id === $userId;
});

Broadcast::channel('users.{userId}.wallet', function (User $user, int $userId): bool {
    return $user->id === $userId;
});

Broadcast::channel('users.{userId}.proxy-checks', function (User $user, int $userId): bool {
    return $user->id === $userId;
});
