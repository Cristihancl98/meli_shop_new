<?php

namespace App\Policies;

use App\Models\Question;
use App\Models\User;

class QuestionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isOperator();
    }

    public function answer(User $user, Question $question): bool
    {
        return $user->isAdmin() || $user->isOperator();
    }
}
