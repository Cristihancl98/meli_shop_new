<?php

namespace App\Interfaces;

use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

interface UserRepositoryInterface
{
    public function paginate(int $perPage = 20): LengthAwarePaginator;
    public function find(int $id): ?User;
    public function create(array $data, string $role): User;
    public function update(User $user, array $data, ?string $role = null): User;
}
