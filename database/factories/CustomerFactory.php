<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\MercadolibreAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    public function definition(): array
    {
        return [
            'meli_customer_id'        => (string) $this->faker->unique()->numerify('##########'),
            'mercadolibre_account_id' => MercadolibreAccount::factory(),
            'name'                    => $this->faker->name(),
            'email'                   => $this->faker->unique()->safeEmail(),
            'phone'                   => $this->faker->phoneNumber(),
            'nickname'                => $this->faker->userName(),
        ];
    }
}
