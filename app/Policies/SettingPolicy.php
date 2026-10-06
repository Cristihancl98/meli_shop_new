<?php

namespace App\Policies;

use App\Models\User;

class SettingPolicy
{
    public function view(User $user): bool
    {
        return $user->isAdmin() || $user->isOperator();
    }

    public function update(User $user): bool
    {
        return $user->isAdmin();
    }
}
