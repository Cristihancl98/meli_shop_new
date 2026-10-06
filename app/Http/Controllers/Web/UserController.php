<?php

namespace App\Http\Controllers\Web;

use App\DTOs\UserDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Models\Role;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class UserController extends Controller
{
    public function __construct(private readonly UserService $userService) {}

    public function index(): View
    {
        $this->authorize('viewAny', User::class);

        $users = $this->userService->list();
        $roles = Role::orderBy('name')->get();

        return view('users.index', compact('users', 'roles'));
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $this->userService->create(UserDTO::fromArray($request->validated()));

        return redirect()->route('users.index')->with('success', 'Usuario registrado.');
    }

    public function edit(int $id): View
    {
        $user = $this->userService->find($id);
        abort_if(!$user, 404);
        $this->authorize('update', $user);

        $roles = Role::orderBy('name')->get();

        return view('users.edit', compact('user', 'roles'));
    }

    public function update(UpdateUserRequest $request, int $id): RedirectResponse
    {
        $user = $this->userService->find($id);
        abort_if(!$user, 404);
        $this->authorize('update', $user);

        $this->userService->update($user, UserDTO::fromArray($request->validated()));

        return redirect()->route('users.index')->with('success', 'Usuario actualizado.');
    }
}
