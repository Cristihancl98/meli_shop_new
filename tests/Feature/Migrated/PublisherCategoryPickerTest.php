<?php

namespace Tests\Feature\Migrated;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PublisherCategoryPickerTest extends TestCase
{
    private function fakeMeli(): void
    {
        Http::fake([
            'api.mercadolibre.com/sites/MCO/categories' => Http::response([
                ['id' => 'MCO1000', 'name' => 'Electrónica, Audio y Video'],
                ['id' => 'MCO1430', 'name' => 'Ropa y Accesorios'],
            ]),
            'api.mercadolibre.com/categories/MCO1000' => Http::response([
                'id' => 'MCO1000', 'name' => 'Electrónica, Audio y Video',
                'path_from_root' => [['id' => 'MCO1000', 'name' => 'Electrónica, Audio y Video']],
                'children_categories' => [['id' => 'MCO3697', 'name' => 'Audífonos', 'total_items_in_this_category' => 10]],
            ]),
            'api.mercadolibre.com/categories/MCO3697' => Http::response([
                'id' => 'MCO3697', 'name' => 'Audífonos',
                'path_from_root' => [['id' => 'MCO1000', 'name' => 'Electrónica, Audio y Video'], ['id' => 'MCO3697', 'name' => 'Audífonos']],
                'children_categories' => [],
            ]),
            'api.mercadolibre.com/categories/MCO999' => Http::response(['message' => 'Category not found', 'error' => 'not_found', 'status' => 404], 404),
        ]);
    }

    public function test_root_level_lists_site_categories(): void
    {
        $this->fakeMeli();
        [$admin] = $this->adminWithAccount();

        $this->actingAs($admin)->getJson(route('publisher.categories'))
            ->assertOk()
            ->assertJsonPath('data.id', null)
            ->assertJsonPath('data.path', [])
            ->assertJsonPath('data.children.0', ['id' => 'MCO1000', 'name' => 'Electrónica, Audio y Video'])
            ->assertJsonPath('data.is_leaf', false);
    }

    public function test_category_level_returns_path_children_and_leaf_flag(): void
    {
        $this->fakeMeli();
        [$admin] = $this->adminWithAccount();

        $this->actingAs($admin)->getJson(route('publisher.categories', ['category_id' => 'MCO1000']))
            ->assertOk()
            ->assertJsonPath('data.children.0', ['id' => 'MCO3697', 'name' => 'Audífonos'])
            ->assertJsonPath('data.is_leaf', false);

        $this->actingAs($admin)->getJson(route('publisher.categories', ['category_id' => 'MCO3697']))
            ->assertOk()
            ->assertJsonPath('data.name', 'Audífonos')
            ->assertJsonPath('data.path.1.name', 'Audífonos')
            ->assertJsonPath('data.children', [])
            ->assertJsonPath('data.is_leaf', true);
    }

    public function test_categories_are_cached(): void
    {
        $this->fakeMeli();
        [$admin] = $this->adminWithAccount();

        $this->actingAs($admin)->getJson(route('publisher.categories', ['category_id' => 'MCO3697']))->assertOk();
        $this->actingAs($admin)->getJson(route('publisher.categories', ['category_id' => 'MCO3697']))->assertOk();

        Http::assertSentCount(1);
    }

    public function test_unknown_or_malformed_category_is_rejected(): void
    {
        $this->fakeMeli();
        [$admin] = $this->adminWithAccount();

        $this->actingAs($admin)->getJson(route('publisher.categories', ['category_id' => 'MCO999']))
            ->assertStatus(422)
            ->assertJsonPath('message', 'La categoría MCO999 no existe en Mercado Libre.');

        $this->actingAs($admin)->getJson(route('publisher.categories', ['category_id' => 'abc']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('category_id');
    }

    public function test_root_error_from_meli_is_reported(): void
    {
        Http::fake(['api.mercadolibre.com/sites/MCO/categories' => Http::response([
            'blocked_by' => 'PolicyAgent', 'code' => 'PA_UNAUTHORIZED_RESULT_FROM_POLICIES', 'status' => 403, 'message' => 'At least one policy returned UNAUTHORIZED.',
        ], 403)]);
        [$admin] = $this->adminWithAccount();

        $this->actingAs($admin)->getJson(route('publisher.categories'))
            ->assertStatus(422)
            ->assertJsonPath('message', 'Mercado Libre no devolvió las categorías: At least one policy returned UNAUTHORIZED.');
    }

    public function test_requires_account_and_publish_permission(): void
    {
        Http::fake();

        $this->actingAs($this->createAdmin())->getJson(route('publisher.categories'))->assertStatus(422);
        $this->actingAs($this->createOperator())->getJson(route('publisher.categories'))->assertForbidden();

        Http::assertNothingSent();
    }

    public function test_publisher_form_renders_category_picker(): void
    {
        [$admin] = $this->adminWithAccount();
        $this->catalog->products = [['titulo' => 'Audífonos', 'precio' => 10, 'peso' => 1, 'sku' => 'B0AUDIO', 'categoriaMeli' => 'MCO3697']];
        Http::fake();

        $this->actingAs($admin)->get(route('publisher.create', ['sku' => 'B0AUDIO']))
            ->assertOk()
            ->assertSee('id="category-open"', false)
            ->assertSee('data-url="' . route('publisher.categories') . '"', false)
            ->assertSee('id="category-modal"', false)
            ->assertSee('value="MCO3697"', false);
    }
}
