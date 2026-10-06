<?php

namespace Tests\Feature\Migrated;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserControllerTest extends TestCase
{
    public function test_admin_lists_users_with_roles(): void
    {
        $admin = $this->createAdmin();
        $this->createOperator();

        $this->getJson('/api/users', $this->authHeaders($admin))
            ->assertOk()
            ->assertJsonPath('data.total', 2)
            ->assertJsonStructure(['data' => ['data' => [['id', 'name', 'email', 'roles']]]]);
    }

    public function test_operator_cannot_list_users(): void
    {
        $this->getJson('/api/users', $this->authHeaders($this->createOperator()))->assertForbidden();
    }

    public function test_admin_registers_user_with_hashed_password(): void
    {
        $admin = $this->createAdmin();

        $this->postJson('/api/users', [
            'name'     => 'Ana Operadora',
            'email'    => 'ana@example.com',
            'password' => 'secreto123',
            'role'     => 'operator',
        ], $this->authHeaders($admin))
            ->assertCreated()
            ->assertJsonPath('data.roles.0.name', 'operator')
            ->assertJsonMissingPath('data.password');

        $user = User::where('email', 'ana@example.com')->first();
        $this->assertTrue(Hash::check('secreto123', $user->password));
        $this->assertTrue($user->isOperator());
    }

    public function test_register_validates_unique_email_and_role(): void
    {
        $admin = $this->createAdmin();

        $this->postJson('/api/users', [
            'name'     => 'Dup',
            'email'    => $admin->email,
            'password' => 'short',
            'role'     => 'superuser',
        ], $this->authHeaders($admin))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email', 'password', 'role']);
    }

    public function test_admin_updates_user_and_role(): void
    {
        $admin    = $this->createAdmin();
        $operator = $this->createOperator();

        $this->putJson("/api/users/{$operator->id}", ['name' => 'Nuevo Nombre', 'role' => 'admin'], $this->authHeaders($admin))
            ->assertOk()
            ->assertJsonPath('data.name', 'Nuevo Nombre');

        $this->assertTrue($operator->fresh()->isAdmin());
        $this->assertFalse($operator->fresh()->isOperator());
    }

    public function test_update_returns_404_for_missing_user(): void
    {
        $this->putJson('/api/users/999999', ['name' => 'x'], $this->authHeaders($this->createAdmin()))->assertNotFound();
    }
}
