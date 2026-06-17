<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['meli_category_id' => 'MCO1000', 'name' => 'Electrónica, Audio y Video'],
            ['meli_category_id' => 'MCO1051', 'name' => 'Computación'],
            ['meli_category_id' => 'MCO1648', 'name' => 'Celulares y Smartphones'],
            ['meli_category_id' => 'MCO1182', 'name' => 'Cámaras y Accesorios'],
            ['meli_category_id' => 'MCO1574', 'name' => 'Videojuegos y Consolas'],
            ['meli_category_id' => 'MCO1430', 'name' => 'Ropa y Accesorios'],
            ['meli_category_id' => 'MCO1246', 'name' => 'Hogar y Muebles'],
            ['meli_category_id' => 'MCO1367', 'name' => 'Herramientas y Construcción'],
            ['meli_category_id' => 'MCO1953', 'name' => 'Deportes y Fitness'],
            ['meli_category_id' => 'MCO1132', 'name' => 'Autos, Motos y Otros'],
            ['meli_category_id' => 'MCO1071', 'name' => 'Juegos y Juguetes'],
            ['meli_category_id' => 'MCO1atenimiento', 'name' => 'Libros, Revistas y Comics'],
            ['meli_category_id' => 'MCO1144', 'name' => 'Música, Películas y Series'],
            ['meli_category_id' => 'MCO3937', 'name' => 'Bebés'],
            ['meli_category_id' => 'MCO1276', 'name' => 'Salud y Belleza'],
            ['meli_category_id' => 'MCO1459', 'name' => 'Alimentos y Bebidas'],
            ['meli_category_id' => 'MCO1743', 'name' => 'Mascotas'],
            ['meli_category_id' => 'MCO1500', 'name' => 'Industrias y Oficinas'],
            ['meli_category_id' => 'MCO1168', 'name' => 'Arte y Artesanías'],
            ['meli_category_id' => 'MCO1039', 'name' => 'Antigüedades y Colecciones'],
        ];

        foreach ($categories as $category) {
            Category::firstOrCreate(
                ['meli_category_id' => $category['meli_category_id']],
                $category
            );
        }
    }
}
