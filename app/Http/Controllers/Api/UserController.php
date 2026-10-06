<?php

namespace App\Http\Controllers\Api;

use App\DTOs\UserDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Models\User;
use App\Services\UserService;
use App\Traits\ApiResponses;
use Illuminate\Http\JsonResponse;

class UserController extends Controller
{
    use ApiResponses;

    public function __construct(private readonly UserService $userService) {}

    public function index(): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        return $this->ok($this->userService->list(), 'Usuarios obtenidos.');
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $this->authorize('create', User::class);

        return $this->ok($this->userService->create(UserDTO::fromArray($request->validated())), 'Usuario registrado correctamente.', 201);
    }

    public function update(UpdateUserRequest $request, int $id): JsonResponse
    {
        if (!$user = $this->userService->find($id)) {
            return $this->notFound('Usuario no encontrado.');
        }

        $this->authorize('update', $user);

        return $this->ok($this->userService->update($user, UserDTO::fromArray($request->validated())), 'Usuario guardado correctamente.');
    }
}
