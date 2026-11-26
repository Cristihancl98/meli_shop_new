<?php

namespace Tests\Feature;

use App\Enums\PriceReviewReason;
use App\Jobs\SyncAmazonPricesJob;
use App\Models\MercadolibreAccount;
use App\Models\Product;
use App\Models\SyncLog;
use App\Services\PricingService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class AmazonPriceSyncTest extends TestCase
{
    private MercadolibreAccount $account;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setSettings(['dollar_price' => 4000]);
        Http::fake([
            'api.mercadolibre.com/items/*' => function (Request $request) {
                $data = $request->data();

                return isset($data['status'])
                    ? Http::response(['id' => 'MCO1', 'status' => $data['status'], 'last_updated' => now()->toIso8601String()])
                    : Http::response(['id' => 'MCO1', 'price' => $data['price'] ?? null, 'last_updated' => now()->toIso8601String()]);
            },
        ]);
    }

    private function listed(string $sku, array $attributes = []): Product
    {
        $this->account ??= $this->adminWithAccount()[1];

        return Product::factory()->create(array_merge([
            'mercadolibre_account_id' => $this->account->id,
            'meli_item_id'            => 'MCO' . crc32($sku),
            'sku'                     => $sku,
            'status'                  => 'active',
            'price'                   => 150000,
            'base_price'              => 20,
            'weight'                  => 1,
        ], $attributes));
    }

    private function amazon(string $sku, array $fields): void
    {
        $this->catalog->products[] = array_merge(['sku' => $sku, 'titulo' => "Producto {$sku}", 'precio' => 20, 'peso' => 1, 'estado' => 1], $fields);
    }

    private function runSync(): void
    {
        SyncAmazonPricesJob::dispatchSync($this->account);
    }

    private function expectedPrice(float $usd, float $weight = 1): float
    {
        return app(PricingService::class)->calculate($usd, $weight)->finalPrice;
    }

    public function test_price_increase_is_updated_automatically_in_mercado_libre(): void
    {
        $product = $this->listed('B0UP');
        $this->amazon('B0UP', ['precio' => 25]);

        $this->runSync();

        $product->refresh();
        $expected = $this->expectedPrice(25);
        $this->assertEquals($expected, (float) $product->price);
        $this->assertEquals(25, (float) $product->base_price);
        $this->assertNull($product->review_status);
        Http::assertSent(fn (Request $request) => $request->method() === 'PUT'
            && str_contains($request->url(), "/items/{$product->meli_item_id}")
            && (float) ($request->data()['price'] ?? 0) === (float) $expected);
    }

    public function test_price_drop_is_not_updated_but_flagged_for_review_once(): void
    {
        $product = $this->listed('B0DOWN');
        $this->amazon('B0DOWN', ['precio' => 15]);

        $this->runSync();

        $product->refresh();
        $this->assertEquals(150000, (float) $product->price);
        $this->assertEquals(20, (float) $product->base_price);
        $this->assertSame('pending', $product->review_status);
        $this->assertSame(PriceReviewReason::PriceDrop, $product->review_reason);
        $this->assertEquals(15, (float) $product->review_amazon_price);
        $this->assertStringContainsString('Amazon bajó de 20,00 a 15,00 USD', $product->review_detail);
        Http::assertNothingSent();

        $flaggedAt = $product->review_flagged_at;
        $this->travel(1)->hours();
        $this->runSync();
        $this->assertEquals($flaggedAt, $product->fresh()->review_flagged_at);
    }

    public function test_dismissed_drop_is_not_flagged_again_until_the_price_changes(): void
    {
        $product = $this->listed('B0KEEP', ['review_status' => 'dismissed', 'review_reason' => 'price_drop', 'review_amazon_price' => 15]);
        $this->amazon('B0KEEP', ['precio' => 15]);

        $this->runSync();
        $this->assertSame('dismissed', $product->fresh()->review_status);

        $this->catalog->products = [];
        $this->amazon('B0KEEP', ['precio' => 12]);
        $this->runSync();
        $this->assertSame('pending', $product->fresh()->review_status);
        $this->assertEquals(12, (float) $product->fresh()->review_amazon_price);
    }

    public function test_listing_missing_on_amazon_is_paused_and_flagged(): void
    {
        $product = $this->listed('B0GONE');
        $this->amazon('B0GONE', ['estado' => -1]);

        $this->runSync();

        $product->refresh();
        $this->assertSame('paused', $product->status);
        $this->assertSame(PriceReviewReason::NotOnAmazon, $product->review_reason);
        Http::assertSent(fn (Request $request) => ($request->data()['status'] ?? null) === 'paused');
    }

    public function test_paused_listings_are_never_reactivated_or_touched(): void
    {
        $product = $this->listed('B0PAUSED', ['status' => 'paused', 'price' => 99000]);
        $this->amazon('B0PAUSED', ['precio' => 40, 'estado' => 1]);

        $this->runSync();

        $this->assertSame('paused', $product->fresh()->status);
        $this->assertEquals(99000, (float) $product->fresh()->price);
        Http::assertNothingSent();
    }

    public function test_products_without_base_price_get_a_baseline_without_touching_meli(): void
    {
        $product = $this->listed('B0IMPORTED', ['base_price' => null]);
        $this->amazon('B0IMPORTED', ['precio' => 33]);

        $this->runSync();

        $this->assertEquals(33, (float) $product->fresh()->base_price);
        $this->assertEquals(150000, (float) $product->fresh()->price);
        Http::assertNothingSent();
    }

    public function test_meli_rejection_on_price_increase_is_flagged_with_the_reason(): void
    {
        Http::swap(new \Illuminate\Http\Client\Factory());
        Http::fake(['api.mercadolibre.com/items/*' => Http::response(['message' => 'price is not modifiable', 'cause' => []], 400)]);
        $product = $this->listed('B0VARIATIONS');
        $this->amazon('B0VARIATIONS', ['precio' => 30]);

        $this->runSync();

        $product->refresh();
        $this->assertEquals(150000, (float) $product->price);
        $this->assertSame(PriceReviewReason::UpdateFailed, $product->review_reason);
        $this->assertStringContainsString('price is not modifiable', $product->review_detail);
    }

    public function test_products_not_in_the_catalog_are_skipped_and_run_is_logged(): void
    {
        $product = $this->listed('B0UNKNOWN');

        $this->runSync();

        $this->assertNull($product->fresh()->review_status);
        $this->assertStringContainsString('1 sin datos de Amazon', SyncLog::latest('id')->value('message'));
    }

    public function test_command_queues_one_job_per_active_account(): void
    {
        Queue::fake();
        $this->listed('B0ANY');

        $this->artisan('sync:amazon-prices')->assertSuccessful();

        Queue::assertPushed(SyncAmazonPricesJob::class, fn ($job) => $job->account->is($this->account));
    }

    public function test_review_page_applies_the_suggested_price_or_keeps_the_current_one(): void
    {
        [$admin, $account] = $this->adminWithAccount();
        $this->account = $account;
        $drop = $this->listed('B0APPLY', ['review_status' => 'pending', 'review_reason' => 'price_drop', 'review_amazon_price' => 15, 'review_flagged_at' => now(), 'review_detail' => 'Amazon bajó']);
        $keep = $this->listed('B0STAY', ['review_status' => 'pending', 'review_reason' => 'price_drop', 'review_amazon_price' => 16, 'review_flagged_at' => now(), 'review_detail' => 'Amazon bajó']);

        $this->actingAs($admin)->get(route('products.price-reviews'))
            ->assertOk()->assertSee('B0APPLY')->assertSee('Aplicar sugerido')->assertSee('Mantener precio');

        $this->post(route('products.price-review.apply', $drop->id))->assertSessionHas('success');
        $this->assertEquals($this->expectedPrice(15), (float) $drop->fresh()->price);
        $this->assertNull($drop->fresh()->review_status);

        $this->post(route('products.price-review.dismiss', $keep->id))->assertSessionHas('success');
        $this->assertSame('dismissed', $keep->fresh()->review_status);
        $this->assertEquals(150000, (float) $keep->fresh()->price);

        $this->get(route('products.index'))->assertDontSee('requieren revisión de precio');
    }

    public function test_operator_can_view_reviews_but_not_act(): void
    {
        [$operator, $account] = $this->operatorWithAccount();
        $this->account = $account;
        $product = $this->listed('B0OP', ['review_status' => 'pending', 'review_reason' => 'price_drop', 'review_amazon_price' => 15, 'review_flagged_at' => now()]);

        $this->actingAs($operator)->get(route('products.price-reviews'))->assertOk()->assertDontSee('Aplicar sugerido');
        $this->post(route('products.price-review.apply', $product->id))->assertForbidden();
        $this->post(route('products.price-reviews.run'))->assertForbidden();
    }

    public function test_products_page_shows_pending_reviews_banner(): void
    {
        [$admin, $account] = $this->adminWithAccount();
        $this->account = $account;
        $this->listed('B0BANNER', ['review_status' => 'pending', 'review_reason' => 'not_on_amazon', 'review_flagged_at' => now()]);

        $this->actingAs($admin)->get(route('products.index'))->assertOk()->assertSee('requieren revisión de precio');
    }
}
