<?php

namespace Tests\Feature;

use App\Models\MercadolibreAccount;
use App\Models\Product;
use Database\Factories\CategoryFactory;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ProductControllerTest extends TestCase
{
    public function test_index_returns_products_for_account(): void
    {
        $admin   = $this->createAdmin();
        $account = MercadolibreAccount::factory()->create(['user_id' => $admin->id]);
        Product::factory()->count(3)->create(['mercadolibre_account_id' => $account->id]);

        $response = $this->getJson('/api/products', $this->authHeaders($admin));

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['success', 'data', 'message', 'errors']);
    }

    public function test_index_returns_error_when_no_account_linked(): void
    {
        $admin = $this->createAdmin();

        $response = $this->getJson('/api/products', $this->authHeaders($admin));

        $response->assertJsonPath('success', false);
    }

    public function test_index_requires_authentication(): void
    {
        $response = $this->getJson('/api/products');

        $response->assertUnauthorized();
    }

    public function test_show_returns_existing_product(): void
    {
        $admin   = $this->createAdmin();
        $account = MercadolibreAccount::factory()->create(['user_id' => $admin->id]);
        $product = Product::factory()->create(['mercadolibre_account_id' => $account->id]);

        $response = $this->getJson("/api/products/{$product->id}", $this->authHeaders($admin));

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $product->id);
    }

    public function test_show_returns_404_for_missing_product(): void
    {
        $admin = $this->createAdmin();

        $response = $this->getJson('/api/products/999999', $this->authHeaders($admin));

        $response->assertNotFound()
            ->assertJsonPath('success', false);
    }

    public function test_store_creates_product_as_admin(): void
    {
        Http::fake([
            '*' => Http::response([
                'id'        => 'MCO123456789',
                'permalink' => 'https://articulo.mercadolibre.com.co/test',
                'status'    => 'active',
            ], 200),
        ]);

        $admin   = $this->createAdmin();
        $account = MercadolibreAccount::factory()->create(['user_id' => $admin->id]);

        $response = $this->postJson('/api/products', [
            'title'          => 'Producto de prueba',
            'price'          => 150000,
            'stock'          => 10,
            'status'         => 'active',
            'condition'      => 'new',
            'listing_type_id'=> 'gold_special',
        ], $this->authHeaders($admin));

        $response->assertJsonPath('success', true);

        $this->assertDatabaseHas('products', ['title' => 'Producto de prueba']);
    }

    public function test_store_returns_403_for_operator(): void
    {
        Http::fake();
        $operator = $this->createOperator();
        MercadolibreAccount::factory()->create(['user_id' => $operator->id]);

        $response = $this->postJson('/api/products', [
            'title'          => 'Producto',
            'price'          => 50000,
            'stock'          => 5,
            'status'         => 'active',
            'condition'      => 'new',
            'listing_type_id'=> 'free',
        ], $this->authHeaders($operator));

        $response->assertForbidden();
    }

    public function test_destroy_soft_deletes_product_as_admin(): void
    {
        Http::fake([
            '*' => Http::response(['id' => 'MCO123', 'status' => 'closed'], 200),
        ]);

        $admin   = $this->createAdmin();
        $account = MercadolibreAccount::factory()->create(['user_id' => $admin->id]);
        $product = Product::factory()->create([
            'mercadolibre_account_id' => $account->id,
            'meli_item_id'            => 'MCO123456001',
        ]);

        $response = $this->deleteJson("/api/products/{$product->id}", [], $this->authHeaders($admin));

        $response->assertOk()->assertJsonPath('success', true);

        $this->assertSoftDeleted('products', ['id' => $product->id]);
    }

    public function test_destroy_returns_403_for_operator(): void
    {
        $operator = $this->createOperator();
        $account  = MercadolibreAccount::factory()->create(['user_id' => $operator->id]);
        $product  = Product::factory()->create(['mercadolibre_account_id' => $account->id]);

        $response = $this->deleteJson("/api/products/{$product->id}", [], $this->authHeaders($operator));

        $response->assertForbidden();
    }
}
