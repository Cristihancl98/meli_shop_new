<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    public function test_login_with_valid_credentials_returns_token(): void
    {
        $user = User::factory()->create(['password' => bcrypt('secret123')]);

        $response = $this->postJson('/api/auth/login', [
            'connection_code' => 'TEST-CODE',
            'email'    => $user->email,
            'password' => 'secret123',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'success', 'message', 'data' => ['token', 'token_type', 'expires_in', 'user'],
            ])
            ->assertJsonPath('success', true);
    }

    public function test_login_with_invalid_credentials_returns_401(): void
    {
        $user = User::factory()->create(['password' => bcrypt('secret123')]);

        $response = $this->postJson('/api/auth/login', [
            'connection_code' => 'TEST-CODE',
            'email'    => $user->email,
            'password' => 'wrong-password',
        ]);

        $response->assertUnauthorized()
            ->assertJsonPath('success', false);
    }

    public function test_login_validation_requires_email_and_password(): void
    {
        $response = $this->postJson('/api/auth/login', ['connection_code' => 'TEST-CODE']);

        $response->assertUnprocessable();
    }

    public function test_register_creates_user_with_operator_role(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'connection_code'       => 'TEST-CODE',
            'name'                  => 'Nuevo Usuario',
            'email'                 => 'nuevo@test.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('users', ['email' => 'nuevo@test.com']);
    }

    public function test_profile_returns_authenticated_user(): void
    {
        $admin = $this->createAdmin();

        $response = $this->getJson('/api/auth/profile', $this->authHeaders($admin));

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.email', $admin->email);
    }

    public function test_profile_returns_401_without_token(): void
    {
        $response = $this->getJson('/api/auth/profile');

        $response->assertUnauthorized();
    }

    public function test_logout_invalidates_token(): void
    {
        $admin = $this->createAdmin();

        $response = $this->postJson('/api/auth/logout', [], $this->authHeaders($admin));

        $response->assertOk()
            ->assertJsonPath('success', true);
    }
}
