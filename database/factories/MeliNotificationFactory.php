<?php

namespace Database\Factories;

use App\Models\MeliNotification;
use App\Models\MercadolibreAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

class MeliNotificationFactory extends Factory
{
    protected $model = MeliNotification::class;

    public function definition(): array
    {
        return [
            'mercadolibre_account_id' => MercadolibreAccount::factory(),
            'type'                    => $this->faker->randomElement(['sale', 'pre_sale_question', 'post_sale_message']),
            'resource'                => '/orders/' . $this->faker->numerify('2##############'),
        ];
    }
}
