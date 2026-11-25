<?php

namespace Tests\Feature;

use App\Models\Product;
use Tests\TestCase;

class ProductListingTest extends TestCase
{
    public function test_cards_view_is_the_default(): void
    {
        [$admin, $account] = $this->adminWithAccount();
        Product::factory()->create(['mercadolibre_account_id' => $account->id, 'title' => 'Taladro de prueba']);

        $this->actingAs($admin)->get(route('products.index'))
            ->assertOk()
            ->assertSee('product-card', false)
            ->assertDontSee('dark-table mb-0 products-table', false)
            ->assertSee('Taladro de prueba');
    }

    public function test_table_view_shows_sortable_headers_and_is_remembered(): void
    {
        [$admin, $account] = $this->adminWithAccount();
        Product::factory()->create(['mercadolibre_account_id' => $account->id, 'sku' => 'B0TABLA']);

        $this->actingAs($admin)->get(route('products.index', ['view' => 'table']))
            ->assertOk()
            ->assertSee('dark-table mb-0 products-table', false)
            ->assertSee('sort=price', false)
            ->assertSee('B0TABLA');

        $this->get(route('products.index'))
            ->assertOk()
            ->assertSee('dark-table mb-0 products-table', false);
    }

    public function test_products_are_sorted_by_the_requested_column_in_both_views(): void
    {
        [$admin, $account] = $this->adminWithAccount();
        foreach ([300000 => 'Producto medio', 100000 => 'Producto barato', 900000 => 'Producto caro'] as $price => $title) {
            Product::factory()->create(['mercadolibre_account_id' => $account->id, 'price' => $price, 'title' => $title]);
        }

        foreach (['cards', 'table'] as $view) {
            $this->actingAs($admin)->get(route('products.index', ['view' => $view, 'sort' => 'price', 'direction' => 'asc']))
                ->assertOk()
                ->assertSeeInOrder(['Producto barato', 'Producto medio', 'Producto caro']);

            $this->get(route('products.index', ['view' => $view, 'sort' => 'price', 'direction' => 'desc']))
                ->assertOk()
                ->assertSeeInOrder(['Producto caro', 'Producto medio', 'Producto barato']);
        }
    }

    public function test_both_views_paginate_twenty_per_page_keeping_sort(): void
    {
        [$admin, $account] = $this->adminWithAccount();
        Product::factory()->count(25)->create(['mercadolibre_account_id' => $account->id]);

        foreach (['cards', 'table'] as $view) {
            $response = $this->actingAs($admin)->get(route('products.index', ['view' => $view, 'sort' => 'stock', 'direction' => 'asc', 'page' => 2]));

            $response->assertOk()->assertSee('Mostrando 21–25', false);
            $this->assertSame(2, substr_count($response->getContent(), 'class="products-pagination'), 'La paginación debe estar arriba y abajo.');
            $this->assertCount(5, $response->viewData('products')->items());
            $this->assertStringContainsString('sort=stock', $response->viewData('products')->url(1));
        }
    }

    public function test_mco_links_to_the_listing_in_mercado_libre_in_both_views(): void
    {
        [$admin, $account] = $this->adminWithAccount();
        Product::factory()->create(['mercadolibre_account_id' => $account->id, 'meli_item_id' => 'MCO111', 'permalink' => 'https://articulo.mercadolibre.com.co/MCO-111-taladro-_JM']);
        Product::factory()->create(['mercadolibre_account_id' => $account->id, 'meli_item_id' => 'MCO222', 'permalink' => null]);

        foreach (['cards', 'table'] as $view) {
            $this->actingAs($admin)->get(route('products.index', ['view' => $view]))
                ->assertOk()
                ->assertSee('href="https://articulo.mercadolibre.com.co/MCO-111-taladro-_JM" target="_blank"', false)
                ->assertSee('href="https://articulo.mercadolibre.com.co/MCO-222" target="_blank"', false);
        }
    }

    public function test_filters_panel_is_collapsible_and_shows_active_count(): void
    {
        [$admin] = $this->adminWithAccount();

        $this->actingAs($admin)->get(route('products.index', ['search' => 'taladro', 'status' => 'active', 'sort' => 'price']))
            ->assertOk()
            ->assertSee('data-bs-target="#product-filters"', false)
            ->assertSee('id="product-filters"', false)
            ->assertSee('2 activos');
    }

    public function test_invalid_sort_is_rejected(): void
    {
        [$admin] = $this->adminWithAccount();

        $this->actingAs($admin)->get(route('products.index', ['sort' => 'password']))
            ->assertSessionHasErrors('sort');
    }
}
