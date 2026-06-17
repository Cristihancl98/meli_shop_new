<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\MercadolibreAccount;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'meli_item_id'             => 'MCO' . $this->faker->unique()->numerify('#########'),
            'mercadolibre_account_id'  => MercadolibreAccount::factory(),
            'category_id'              => Category::inRandomOrder()->first()?->id,
            'title'                    => $this->faker->sentence(4),
            'description'              => $this->faker->paragraph(),
            'price'                    => $this->faker->randomFloat(2, 10000, 5000000),
            'stock'                    => $this->faker->numberBetween(0, 100),
            'status'                   => $this->faker->randomElement(['active', 'paused', 'closed']),
            'condition'                => $this->faker->randomElement(['new', 'used']),
            'listing_type_id'          => $this->faker->randomElement(['free', 'bronze', 'gold_special']),
            'thumbnail'                => $this->faker->imageUrl(300, 300, 'products'),
            'permalink'                => $this->faker->url(),
            'last_sync'                => now(),
        ];
    }

    public function active(): static
    {
        return $this->state(['status' => 'active', 'stock' => $this->faker->numberBetween(1, 100)]);
    }

    public function paused(): static
    {
        return $this->state(['status' => 'paused']);
    }

    public function outOfStock(): static
    {
        return $this->state(['stock' => 0]);
    }
}
