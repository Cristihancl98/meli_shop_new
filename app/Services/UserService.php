<?php

namespace App\Services;

use App\DTOs\UserDTO;
use App\Interfaces\UserRepositoryInterface;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

class UserService
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository
    ) {}

    public function list(): LengthAwarePaginator
    {
        return $this->userRepository->paginate();
    }

    public function find(int $id): ?User
    {
        return $this->userRepository->find($id);
    }

    public function create(UserDTO $dto): User
    {
        return $this->userRepository->create($dto->attributes(), $dto->role);
    }

    public function update(User $user, UserDTO $dto): User
    {
        return $this->userRepository->update($user, $dto->attributes(), $dto->role);
    }
}
