<?php

namespace Database\Factories;

use App\Models\MercadolibreAccount;
use App\Models\PostSaleMessage;
use Illuminate\Database\Eloquent\Factories\Factory;

class PostSaleMessageFactory extends Factory
{
    protected $model = PostSaleMessage::class;

    public function definition(): array
    {
        return [
            'mercadolibre_account_id' => MercadolibreAccount::factory(),
            'pack_id'                 => $this->faker->numerify('2##############'),
            'meli_message_id'         => $this->faker->unique()->uuid(),
            'text'                    => $this->faker->sentence(),
            'from_seller'             => false,
            'status'                  => 'available',
            'sent_at'                 => now(),
            'seen'                    => false,
        ];
    }
}
