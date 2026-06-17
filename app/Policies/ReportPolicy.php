<?php

namespace App\Policies;

use App\Models\User;

class ReportPolicy
{
    public function view(User $user): bool
    {
        return $user->isAdmin() || $user->isOperator();
    }

    public function export(User $user): bool
    {
        return $user->isAdmin();
    }
}
