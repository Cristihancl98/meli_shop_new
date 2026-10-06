<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isOperator();
    }

    public function view(User $user, Order $order): bool
    {
        return $user->isAdmin() || $user->isOperator();
    }

    public function sync(User $user): bool
    {
        return $user->isAdmin();
    }

    public function updateFinalPrice(User $user, Order $order): bool
    {
        return $user->isAdmin();
    }

    public function downloadLabel(User $user, Order $order): bool
    {
        return $user->isAdmin() || $user->isOperator();
    }
}
