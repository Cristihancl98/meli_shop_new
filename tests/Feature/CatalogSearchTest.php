<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\PublishBatch;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CatalogSearchTest extends TestCase
{
    private function product(string $sku, array $overrides = []): array
    {
        return array_merge([
            'sku'             => $sku,
            'titulo'          => "Producto {$sku}",
            'precio'          => 25,
            'peso'            => 1.5,
            'imagenes'        => ["https://img.test/{$sku}.jpg"],
            'fechaInserccion' => '2026-11-01T10:00:00Z',
        ], $overrides);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->setSettings(['dollar_price' => 4000]);
        $this->catalog->products = [
            $this->product('B0OLD', ['fechaInserccion' => '2026-10-01T10:00:00Z']),
            $this->product('B0NEW', ['fechaInserccion' => '2026-11-20T10:00:00Z']),
            $this->product('B0NOPRICE', ['precio' => null]),
            $this->product('B0NOWEIGHT', ['peso' => 0]),
            $this->product('B0NOIMG', ['imagenes' => []]),
            $this->product('B0LONG', ['titulo' => str_repeat('Título muy largo ', 5)]),
            $this->product('B0SHORTMELI', ['titulo' => str_repeat('Título muy largo ', 5), 'tituloMeli' => 'Título corto para MeLi']),
            $this->product('B0HEAVY', ['peso' => 12, 'precio' => 80]),
        ];
    }

    private function search(array $query)
    {
        [$admin, $account] = $this->adminWithAccount();
        $this->actingAs($admin);

        return [$this->get(route('publisher.catalog', $query)), $account];
    }

    public function test_recent_mode_needs_no_term_and_lists_newest_first(): void
    {
        [$response] = $this->search(['mode' => 'recent', 'only_publishable' => 1]);

        $response->assertOk()->assertSeeInOrder(['B0NEW', 'B0OLD']);
    }

    public function test_only_publishable_keeps_items_with_price_weight_short_title_and_images(): void
    {
        [$response] = $this->search(['mode' => 'recent', 'only_publishable' => 1]);

        $response->assertOk()
            ->assertSee('B0NEW')
            ->assertSee('B0SHORTMELI')
            ->assertSee('Título corto para MeLi')
            ->assertDontSee('B0NOPRICE')
            ->assertDontSee('B0NOWEIGHT')
            ->assertDontSee('B0NOIMG')
            ->assertDontSee('B0LONG');
    }

    public function test_without_the_filter_incomplete_items_show_what_is_missing(): void
    {
        [$response] = $this->search(['mode' => 'recent', 'only_publishable' => 0]);

        $response->assertOk()
            ->assertSee('B0NOPRICE')
            ->assertSee('Falta: precio')
            ->assertSee('Falta: imágenes')
            ->assertSee('caracteres (máx. 60)', false);
    }

    public function test_price_and_weight_ranges_filter_results(): void
    {
        [$response] = $this->search(['mode' => 'recent', 'only_publishable' => 1, 'min_weight' => 10]);
        $response->assertOk()->assertSee('B0HEAVY')->assertDontSee('B0NEW');

        $this->get(route('publisher.catalog', ['mode' => 'recent', 'only_publishable' => 1, 'max_price' => 30]))
            ->assertOk()->assertSee('B0NEW')->assertDontSee('B0HEAVY');
    }

    public function test_results_show_price_in_cop_and_flag_already_published_skus(): void
    {
        [$admin, $account] = $this->adminWithAccount();
        Product::factory()->create(['mercadolibre_account_id' => $account->id, 'sku' => 'B0OLD']);

        $this->actingAs($admin)->get(route('publisher.catalog', ['mode' => 'recent', 'only_publishable' => 1]))
            ->assertOk()
            ->assertSee('Ya publicado')
            ->assertSee('value="B0OLD" class="item-check" aria-label="Marcar B0OLD" disabled', false)
            ->assertSee('Precio COP');
    }

    public function test_title_and_category_modes_require_a_term(): void
    {
        [$response] = $this->search(['mode' => 'title']);

        $response->assertSessionHasErrors('q');
    }

    public function test_max_without_min_is_valid_and_max_below_min_is_rejected(): void
    {
        [$response] = $this->search(['mode' => 'recent', 'max_price' => 50]);
        $response->assertOk();

        $this->get(route('publisher.catalog', ['mode' => 'recent', 'min_price' => 50, 'max_price' => 10]))
            ->assertSessionHasErrors('max_price');
    }

    public function test_checked_results_create_a_publish_batch(): void
    {
        [$admin] = $this->adminWithAccount();
        Http::fake(['api.mercadolibre.com/sites/MCO/domain_discovery/search*' => Http::response([['category_id' => 'MCO5555']])]);

        $this->actingAs($admin)->post(route('publisher.batches.store'), ['skus' => ['B0NEW', 'B0OLD']])
            ->assertRedirect();

        $batch = PublishBatch::latest('id')->firstOrFail();
        $this->assertEqualsCanonicalizing(['B0NEW', 'B0OLD'], $batch->items()->pluck('sku')->all());
        $this->assertSame(2, $batch->items()->where('status', 'ready')->count());
    }
}
