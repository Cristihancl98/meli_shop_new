<?php

namespace App\Policies;

use App\Models\User;

class PostSaleMessagePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isOperator();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isOperator();
    }
}
