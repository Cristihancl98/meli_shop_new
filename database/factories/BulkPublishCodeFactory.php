<?php

namespace Database\Factories;

use App\Models\BulkPublishCode;
use App\Models\MercadolibreAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

class BulkPublishCodeFactory extends Factory
{
    protected $model = BulkPublishCode::class;

    public function definition(): array
    {
        return [
            'mercadolibre_account_id' => MercadolibreAccount::factory(),
            'sku'                     => strtoupper($this->faker->bothify('B0########')),
            'status'                  => 'pending',
        ];
    }
}
