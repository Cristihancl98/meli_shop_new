<?php

namespace Tests\Feature\Migrated;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MeliCategoryControllerTest extends TestCase
{
    public function test_lists_site_categories(): void
    {
        [$admin] = $this->adminWithAccount();
        Http::fake(['api.mercadolibre.com/sites/MCO/categories' => Http::response([['id' => 'MCO1000', 'name' => 'Electrónica']])]);

        $this->getJson('/api/meli/categories', $this->authHeaders($admin))
            ->assertOk()
            ->assertJsonPath('data.0.id', 'MCO1000');
    }

    public function test_shows_category_detail(): void
    {
        [$admin] = $this->adminWithAccount();
        Http::fake(['api.mercadolibre.com/categories/MCO1000' => Http::response(['id' => 'MCO1000', 'children_categories' => []])]);

        $this->getJson('/api/meli/categories/MCO1000', $this->authHeaders($admin))
            ->assertOk()
            ->assertJsonPath('data.id', 'MCO1000');
    }

    public function test_predicts_category_from_title(): void
    {
        [$operator] = $this->operatorWithAccount();
        Http::fake(['api.mercadolibre.com/sites/MCO/domain_discovery/search*' => Http::response([['category_id' => 'MCO3697']])]);

        $this->getJson('/api/meli/categories/predict?title=audifonos%20bluetooth', $this->authHeaders($operator))
            ->assertOk()
            ->assertJsonPath('data.0.category_id', 'MCO3697');
    }

    public function test_predict_requires_title(): void
    {
        [$admin] = $this->adminWithAccount();

        $this->getJson('/api/meli/categories/predict', $this->authHeaders($admin))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['title']);
    }
}
