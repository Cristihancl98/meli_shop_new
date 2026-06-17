<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\MercadolibreAccount;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        return [
            'meli_order_id'           => (string) $this->faker->unique()->numerify('##########'),
            'mercadolibre_account_id' => MercadolibreAccount::factory(),
            'customer_id'             => Customer::factory(),
            'status'                  => $this->faker->randomElement(['paid', 'pending', 'cancelled', 'delivered']),
            'payment_status'          => $this->faker->randomElement(['approved', 'pending', 'rejected']),
            'shipping_status'         => $this->faker->randomElement(['ready_to_ship', 'shipped', 'delivered', 'not_delivered']),
            'total_amount'            => $this->faker->randomFloat(2, 10000, 5000000),
            'order_date'              => $this->faker->dateTimeBetween('-1 year', 'now'),
        ];
    }

    public function paid(): static
    {
        return $this->state([
            'status'         => 'paid',
            'payment_status' => 'approved',
        ]);
    }

    public function pending(): static
    {
        return $this->state([
            'status'         => 'pending',
            'payment_status' => 'pending',
        ]);
    }
}
