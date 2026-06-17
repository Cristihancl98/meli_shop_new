<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@marketplace.com'],
            [
                'name'              => 'Administrador',
                'password'          => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        $adminRole = Role::where('name', 'admin')->first();
        if ($adminRole && !$admin->roles()->where('role_id', $adminRole->id)->exists()) {
            $admin->roles()->attach($adminRole->id);
        }

        $operator = User::firstOrCreate(
            ['email' => 'operador@marketplace.com'],
            [
                'name'              => 'Operador',
                'password'          => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        $operatorRole = Role::where('name', 'operator')->first();
        if ($operatorRole && !$operator->roles()->where('role_id', $operatorRole->id)->exists()) {
            $operator->roles()->attach($operatorRole->id);
        }
    }
}
