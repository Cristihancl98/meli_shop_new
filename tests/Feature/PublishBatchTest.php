<?php

namespace Tests\Feature;

use App\Enums\BatchItemStatus;
use App\Models\Product;
use App\Models\PublishBatch;
use App\Models\PublishBatchItem;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PublishBatchTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->setSettings(['dollar_price' => 4000]);
        $this->catalog->products = [
            ['sku' => 'B0LAMP', 'titulo' => 'Lámpara de escritorio LED', 'precio' => 20, 'peso' => 1, 'marca' => 'Lumi', 'imagenes' => ['https://img.test/lamp.jpg']],
            ['sku' => 'B0FAIL', 'titulo' => 'Producto rechazado', 'precio' => 30, 'peso' => 1, 'marca' => 'Acme', 'imagenes' => ['https://img.test/fail.jpg']],
            ['sku' => 'B0NOBRAND', 'titulo' => 'Sin marca', 'precio' => 10, 'peso' => 1, 'imagenes' => ['https://img.test/nb.jpg']],
        ];
    }

    private function fakeMeli(): void
    {
        Http::fake([
            'api.mercadolibre.com/sites/MCO/domain_discovery/search*' => Http::response([['category_id' => 'MCO5555']]),
            'api.mercadolibre.com/items/*/description'               => Http::response(['text' => 'ok']),
            'api.mercadolibre.com/items' => function (Request $request) {
                $skus = collect($request->data()['attributes'] ?? [])->where('id', 'SELLER_SKU')->pluck('value_name');

                return ($request->data()['title'] ?? '') === 'Producto rechazado'
                    ? Http::response(['message' => 'Validation error', 'cause' => [['code' => 'item.title.invalid', 'message' => 'El título no es válido']]], 400)
                    : Http::response(['id' => 'MCO' . crc32($skus->first() ?? 'x'), 'permalink' => 'https://articulo.mercadolibre.com.co/MCO-1-_JM']);
            },
        ]);
    }

    private function createBatch(string $codes): PublishBatch
    {
        [$admin] = $this->adminWithAccount();
        $this->fakeMeli();

        $this->actingAs($admin)->post(route('publisher.batches.store'), ['codes' => $codes])->assertRedirect();

        return PublishBatch::latest('id')->firstOrFail();
    }

    private function item(PublishBatch $batch, string $sku): PublishBatchItem
    {
        return $batch->items()->where('sku', $sku)->firstOrFail();
    }

    public function test_new_batch_dedupes_flags_published_skus_and_scrapes_each_one(): void
    {
        [$admin, $account] = $this->adminWithAccount();
        Product::factory()->create(['mercadolibre_account_id' => $account->id, 'sku' => 'B0DONE']);
        $this->fakeMeli();

        $this->actingAs($admin)->post(route('publisher.batches.store'), ['codes' => "B0LAMP\nb0lamp, B0DONE B0MISSING"])
            ->assertRedirect();

        $batch = PublishBatch::latest('id')->firstOrFail();
        $this->assertSame(3, $batch->items()->count());

        $lamp = $this->item($batch, 'B0LAMP');
        $this->assertSame(BatchItemStatus::Ready, $lamp->status);
        $this->assertSame('MCO5555', $lamp->data['suggested_category']);
        $this->assertGreaterThan(0, $lamp->data['final_price']);

        $this->assertSame(BatchItemStatus::Duplicate, $this->item($batch, 'B0DONE')->status);
        $this->assertSame(BatchItemStatus::NotFound, $this->item($batch, 'B0MISSING')->status);
    }

    public function test_batch_page_lists_items_for_review(): void
    {
        $batch = $this->createBatch('B0LAMP B0MISSING');

        $this->get(route('publisher.batches.show', $batch->id))
            ->assertOk()
            ->assertSee('Lámpara de escritorio LED')
            ->assertSee('Listo para revisar')
            ->assertSee('No encontrado')
            ->assertSee('Publicar marcados', false);
    }

    public function test_checked_items_are_published_and_failures_keep_meli_reason(): void
    {
        $batch = $this->createBatch('B0LAMP B0FAIL B0NOBRAND');
        $ids   = $batch->items()->pluck('id')->all();

        $this->post(route('publisher.batches.publish', $batch->id), ['items' => $ids])
            ->assertRedirect(route('publisher.batches.show', $batch->id));

        $lamp = $this->item($batch, 'B0LAMP');
        $this->assertSame(BatchItemStatus::Published, $lamp->status);
        $this->assertNotNull($lamp->product_id);
        $this->assertSame($lamp->meli_item_id, Product::find($lamp->product_id)->meli_item_id);

        $failed = $this->item($batch, 'B0FAIL');
        $this->assertSame(BatchItemStatus::Failed, $failed->status);
        $this->assertStringContainsString('El título no es válido', $failed->error);

        $noBrand = $this->item($batch, 'B0NOBRAND');
        $this->assertSame(BatchItemStatus::Failed, $noBrand->status);
        $this->assertStringContainsString('marca', $noBrand->error);
    }

    public function test_only_ready_or_failed_items_are_published(): void
    {
        $batch = $this->createBatch('B0MISSING');

        $this->post(route('publisher.batches.publish', $batch->id), ['items' => $batch->items()->pluck('id')->all()])
            ->assertSessionHas('error');

        $this->assertSame(BatchItemStatus::NotFound, $this->item($batch, 'B0MISSING')->status);
    }

    public function test_not_found_items_can_be_searched_again(): void
    {
        $batch = $this->createBatch('B0LATE');
        $this->catalog->products[] = ['sku' => 'B0LATE', 'titulo' => 'Llegó después', 'precio' => 15, 'peso' => 1, 'marca' => 'Tarde', 'imagenes' => ['https://img.test/late.jpg']];

        $this->post(route('publisher.batches.rescrape', $batch->id), ['items' => $batch->items()->pluck('id')->all()])
            ->assertSessionHas('success');

        $this->assertSame(BatchItemStatus::Ready, $this->item($batch, 'B0LATE')->status);
    }

    public function test_server_side_scraper_is_used_when_the_catalog_does_not_have_the_sku(): void
    {
        $this->setSettings(['scraping_url' => 'https://scraper.test']);
        Http::fake([
            'scraper.test/*' => Http::response(['sku' => 'B0WEB', 'titulo' => 'Traído por el scraper', 'precio' => 12, 'peso' => 1, 'marca' => 'Web', 'imagenes' => ['https://img.test/web.jpg']]),
        ]);

        $batch = $this->createBatch('B0WEB');

        $item = $this->item($batch, 'B0WEB');
        $this->assertSame(BatchItemStatus::Ready, $item->status);
        $this->assertSame('Traído por el scraper', $item->title());
        Http::assertSent(fn (Request $request) => str_starts_with($request->url(), 'https://scraper.test/api/v1/app/scriping/B0WEB'));
    }

    public function test_correct_button_opens_the_editor_prefilled_and_publishing_marks_the_item(): void
    {
        $batch = $this->createBatch('B0FAIL');
        $this->post(route('publisher.batches.publish', $batch->id), ['items' => $batch->items()->pluck('id')->all()]);
        $item = $this->item($batch, 'B0FAIL');

        $this->get(route('publisher.create', ['sku' => 'B0FAIL', 'batch_item' => $item->id]))
            ->assertOk()
            ->assertSee('Corrigiendo')
            ->assertSee('El título no es válido')
            ->assertSee('value="Producto rechazado"', false)
            ->assertSee('name="batch_item_id" value="' . $item->id . '"', false);

        $this->post(route('publisher.store'), [
            'sku' => 'B0FAIL', 'title' => 'Título corregido', 'meli_category_id' => 'MCO5555', 'final_price' => 150000,
            'base_price' => 30, 'quantity' => 1, 'weight' => 1, 'brand' => 'Acme',
            'pictures' => ['https://img.test/fail.jpg'], 'batch_item_id' => $item->id,
        ])->assertRedirect(route('publisher.batches.show', $batch->id));

        $item->refresh();
        $this->assertSame(BatchItemStatus::Published, $item->status);
        $this->assertSame('MCO' . crc32('B0FAIL'), $item->meli_item_id);
        $this->assertNull($item->error);
    }

    public function test_operator_cannot_use_bulk_publishing(): void
    {
        [$operator] = $this->operatorWithAccount();

        $this->actingAs($operator)->get(route('publisher.batches.index'))->assertForbidden();
        $this->actingAs($operator)->post(route('publisher.batches.store'), ['codes' => 'B0LAMP'])->assertForbidden();
    }

    public function test_batches_of_other_accounts_are_not_visible(): void
    {
        $batch = $this->createBatch('B0LAMP');

        $otherAdmin = $this->createAdmin();
        \App\Models\MercadolibreAccount::factory()->create(['user_id' => $otherAdmin->id]);
        $this->actingAs($otherAdmin)->get(route('publisher.batches.show', $batch->id))->assertNotFound();
    }
}
