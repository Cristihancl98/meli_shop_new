<?php

namespace Database\Factories;

use App\Models\MercadolibreAccount;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class MercadolibreAccountFactory extends Factory
{
    protected $model = MercadolibreAccount::class;

    public function definition(): array
    {
        return [
            'user_id'       => User::factory(),
            'meli_user_id'  => (string) $this->faker->unique()->numerify('##########'),
            'access_token'  => 'APP_USR-' . $this->faker->sha256(),
            'refresh_token' => 'TG-' . $this->faker->sha256(),
            'expires_at'    => now()->addHours(6),
            'nickname'      => $this->faker->userName(),
            'email'         => $this->faker->safeEmail(),
            'is_active'     => true,
        ];
    }
}
