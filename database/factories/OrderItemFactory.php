<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderItemFactory extends Factory
{
    protected $model = OrderItem::class;

    public function definition(): array
    {
        $unitPrice  = $this->faker->randomFloat(2, 10000, 2000000);
        $quantity   = $this->faker->numberBetween(1, 5);

        return [
            'order_id'    => Order::factory(),
            'product_id'  => Product::factory(),
            'meli_item_id'=> 'MCO' . $this->faker->numerify('#########'),
            'title'       => $this->faker->sentence(4),
            'quantity'    => $quantity,
            'unit_price'  => $unitPrice,
            'total_price' => round($unitPrice * $quantity, 2),
        ];
    }
}
