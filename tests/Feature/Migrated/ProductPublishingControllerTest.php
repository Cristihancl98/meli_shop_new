<?php

namespace Tests\Feature\Migrated;

use App\Models\Product;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ProductPublishingControllerTest extends TestCase
{
    private function publishPayload(array $overrides = []): array
    {
        return array_merge([
            'sku'              => 'B0PUBLISH',
            'title'            => 'Audífonos inalámbricos',
            'meli_category_id' => 'MCO3697',
            'final_price'      => 189999.6,
            'base_price'       => 39.99,
            'quantity'         => 5,
            'weight'           => 1.5,
            'brand'            => 'Sony',
            'pictures'         => ['https://img.test/1.jpg', 'https://img.test/2.jpg'],
            'description'      => 'Producto original importado.',
            'height'           => 10,
            'width'            => 20,
            'length'           => 5,
            'model'            => 'WH-1000',
            'ean'              => '1234567890123',
            'extra_attributes' => [['id' => 'COLOR', 'value' => 'Negro']],
        ], $overrides);
    }

    public function test_publish_creates_item_in_meli_and_locally(): void
    {
        [$admin, $account] = $this->adminWithAccount();
        $this->setSettings([
            'listing_type'         => 'gold_pro',
            'manufacturing_days'   => 15,
            'warranty_days'        => 90,
            'description_template' => 'Garantía de 90 días.',
        ]);

        Http::fake([
            'api.mercadolibre.com/items/MCO900/description' => Http::response(['text' => 'ok']),
            'api.mercadolibre.com/items'                    => Http::response(['id' => 'MCO900', 'permalink' => 'https://meli.test/MCO900'], 201),
        ]);

        $this->postJson('/api/products/publish', $this->publishPayload(), $this->authHeaders($admin))
            ->assertCreated()
            ->assertJsonPath('data.meli_item_id', 'MCO900')
            ->assertJsonPath('data.sku', 'B0PUBLISH');

        Http::assertSent(function (Request $request) {
            if ($request->url() !== 'https://api.mercadolibre.com/items') {
                return false;
            }

            $attributeIds = array_column($request['attributes'], 'id');

            return $request['price'] == 190000
                && $request['listing_type_id'] === 'gold_pro'
                && $request['sale_terms'][0]['value_name'] === '15 días'
                && $request['pictures'][0]['source'] === 'https://img.test/1.jpg'
                && in_array('SELLER_SKU', $attributeIds, true)
                && in_array('GTIN', $attributeIds, true)
                && in_array('COLOR', $attributeIds, true)
                && in_array('HEIGHT', $attributeIds, true);
        });

        Http::assertSent(fn (Request $request) => str_ends_with($request->url(), '/items/MCO900/description')
            && $request['plain_text'] === "Producto original importado.\n\nGarantía de 90 días.");

        $this->assertDatabaseHas('products', [
            'mercadolibre_account_id' => $account->id,
            'meli_item_id'            => 'MCO900',
            'sku'                     => 'B0PUBLISH',
            'published_by'            => $admin->id,
            'stock'                   => 5,
            'meli_category_id'        => 'MCO3697',
            'description_synced'      => true,
        ]);
        $this->assertNotNull(\App\Models\Product::where('meli_item_id', 'MCO900')->value('published_at'));
    }

    public function test_publish_returns_502_when_meli_rejects(): void
    {
        [$admin] = $this->adminWithAccount();

        Http::fake(['api.mercadolibre.com/items' => Http::response(['message' => 'validation_error'], 400)]);

        $this->postJson('/api/products/publish', $this->publishPayload(), $this->authHeaders($admin))
            ->assertStatus(502)
            ->assertJsonPath('success', false);

        $this->assertDatabaseMissing('products', ['sku' => 'B0PUBLISH']);
    }

    public function test_publish_rejects_duplicated_sku(): void
    {
        [$admin, $account] = $this->adminWithAccount();
        Product::factory()->create(['mercadolibre_account_id' => $account->id, 'sku' => 'B0PUBLISH']);
        Http::fake();

        $this->postJson('/api/products/publish', $this->publishPayload(), $this->authHeaders($admin))
            ->assertUnprocessable();

        Http::assertNothingSent();
    }

    public function test_publish_validates_payload(): void
    {
        [$admin] = $this->adminWithAccount();

        $this->postJson('/api/products/publish', ['title' => str_repeat('x', 61)], $this->authHeaders($admin))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['sku', 'title', 'meli_category_id', 'final_price', 'pictures']);
    }

    public function test_publish_is_forbidden_for_operator(): void
    {
        [$operator] = $this->operatorWithAccount();

        $this->postJson('/api/products/publish', $this->publishPayload(), $this->authHeaders($operator))
            ->assertForbidden();
    }

    public function test_pause_and_activate_change_status(): void
    {
        [$admin, $account] = $this->adminWithAccount();
        $product = Product::factory()->active()->create(['mercadolibre_account_id' => $account->id, 'meli_item_id' => 'MCO111']);

        Http::fake(['api.mercadolibre.com/items/MCO111' => Http::sequence()
            ->push(['id' => 'MCO111', 'status' => 'paused'])
            ->push(['id' => 'MCO111', 'status' => 'active'])]);

        $this->postJson("/api/products/{$product->id}/pause", [], $this->authHeaders($admin))
            ->assertOk()
            ->assertJsonPath('data.status', 'paused');

        $this->postJson("/api/products/{$product->id}/activate", [], $this->authHeaders($admin))
            ->assertOk()
            ->assertJsonPath('data.status', 'active');
    }

    public function test_pause_fails_when_meli_does_not_confirm(): void
    {
        [$admin, $account] = $this->adminWithAccount();
        $product = Product::factory()->active()->create(['mercadolibre_account_id' => $account->id, 'meli_item_id' => 'MCO111']);

        Http::fake(['api.mercadolibre.com/items/MCO111' => Http::response(['status' => 400, 'message' => 'error'], 400)]);

        $this->postJson("/api/products/{$product->id}/pause", [], $this->authHeaders($admin))->assertStatus(502);
        $this->assertSame('active', $product->fresh()->status);
    }

    public function test_cannot_touch_products_from_another_account(): void
    {
        [$admin] = $this->adminWithAccount();
        $foreign = Product::factory()->create();

        $this->postJson("/api/products/{$foreign->id}/pause", [], $this->authHeaders($admin))->assertNotFound();
    }

    public function test_update_listing_updates_meli_and_local_data(): void
    {
        [$admin, $account] = $this->adminWithAccount();
        $product = Product::factory()->create(['mercadolibre_account_id' => $account->id, 'meli_item_id' => 'MCO222']);

        Http::fake([
            'api.mercadolibre.com/items/MCO222/description*' => Http::response(['last_updated' => now()->toIso8601String()]),
            'api.mercadolibre.com/items/MCO222'              => Http::response(['id' => 'MCO222', 'last_updated' => now()->toIso8601String()]),
        ]);

        $this->patchJson("/api/products/{$product->id}/listing", [
            'title'       => 'Nuevo título',
            'price'       => 150000,
            'quantity'    => 7,
            'pictures'    => ['https://img.test/new.jpg'],
            'description' => 'Nueva descripción',
        ], $this->authHeaders($admin))
            ->assertOk()
            ->assertJsonPath('data.title', 'Nuevo título')
            ->assertJsonPath('data.stock', 7);

        $this->assertSame('https://img.test/new.jpg', $product->fresh()->thumbnail);
        $this->assertSame('Nueva descripción', $product->fresh()->description);
    }

    public function test_update_listing_requires_some_field(): void
    {
        [$admin, $account] = $this->adminWithAccount();
        $product = Product::factory()->create(['mercadolibre_account_id' => $account->id]);

        $this->patchJson("/api/products/{$product->id}/listing", [], $this->authHeaders($admin))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['listing']);
    }

    public function test_archive_soft_deletes_product(): void
    {
        [$admin, $account] = $this->adminWithAccount();
        $product = Product::factory()->create(['mercadolibre_account_id' => $account->id]);

        $this->postJson("/api/products/{$product->id}/archive", [], $this->authHeaders($admin))->assertOk();

        $this->assertSoftDeleted('products', ['id' => $product->id]);
    }

    public function test_summary_counts_by_status_and_dollar(): void
    {
        [$admin, $account] = $this->adminWithAccount();
        Product::factory()->count(2)->active()->create(['mercadolibre_account_id' => $account->id]);
        Product::factory()->paused()->create(['mercadolibre_account_id' => $account->id]);
        $this->setSettings(['dollar_price' => 4000, 'current_dollar_price' => 4150]);

        $this->getJson('/api/products/summary', $this->authHeaders($admin))
            ->assertOk()
            ->assertJsonPath('data.total', 3)
            ->assertJsonPath('data.active', 2)
            ->assertJsonPath('data.paused', 1)
            ->assertJsonPath('data.store_dollar', 4000)
            ->assertJsonPath('data.current_dollar', 4150);
    }

    public function test_find_by_sku(): void
    {
        [$admin, $account] = $this->adminWithAccount();
        Product::factory()->create(['mercadolibre_account_id' => $account->id, 'sku' => 'B0FIND']);

        $this->getJson('/api/products/sku/B0FIND', $this->authHeaders($admin))
            ->assertOk()
            ->assertJsonPath('data.exists', true)
            ->assertJsonCount(1, 'data.products');

        $this->getJson('/api/products/sku/NOPE', $this->authHeaders($admin))
            ->assertOk()
            ->assertJsonPath('data.exists', false);
    }

    public function test_products_index_filters_by_sku(): void
    {
        [$admin, $account] = $this->adminWithAccount();
        Product::factory()->create(['mercadolibre_account_id' => $account->id, 'sku' => 'B0ONE']);
        Product::factory()->create(['mercadolibre_account_id' => $account->id, 'sku' => 'B0TWO']);

        $this->getJson('/api/products?sku=B0ONE', $this->authHeaders($admin))
            ->assertOk()
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.data.0.sku', 'B0ONE');
    }
}
