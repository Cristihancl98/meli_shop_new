<?php

namespace App\Policies;

use App\Models\User;

class MeliNotificationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isOperator();
    }

    public function update(User $user): bool
    {
        return $user->isAdmin() || $user->isOperator();
    }
}
