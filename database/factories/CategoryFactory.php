<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        return [
            'meli_category_id' => 'MCO' . $this->faker->unique()->numerify('####'),
            'name'             => $this->faker->words(2, true),
        ];
    }
}
