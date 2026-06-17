<?php

namespace Tests;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    protected function createAdmin(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('name', 'admin')->first());
        return $user;
    }

    protected function createOperator(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('name', 'operator')->first());
        return $user;
    }

    protected function authHeaders(User $user): array
    {
        $token = JWTAuth::fromUser($user);
        return ['Authorization' => 'Bearer ' . $token, 'Accept' => 'application/json'];
    }
}
