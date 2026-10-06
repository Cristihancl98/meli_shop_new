<?php

namespace App\Repositories;

use App\Interfaces\UserRepositoryInterface;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class UserRepository implements UserRepositoryInterface
{
    public function paginate(int $perPage = 20): LengthAwarePaginator
    {
        return User::with('roles:id,name')->orderBy('name')->paginate($perPage);
    }

    public function find(int $id): ?User
    {
        return User::with('roles:id,name')->find($id);
    }

    public function create(array $data, string $role): User
    {
        return DB::transaction(function () use ($data, $role) {
            $user = User::create($data);
            $user->syncRole($role);
            return $user->load('roles:id,name');
        });
    }

    public function update(User $user, array $data, ?string $role = null): User
    {
        return DB::transaction(function () use ($user, $data, $role) {
            $user->update($data);
            if ($role) {
                $user->syncRole($role);
            }
            return $user->fresh('roles:id,name');
        });
    }
}
