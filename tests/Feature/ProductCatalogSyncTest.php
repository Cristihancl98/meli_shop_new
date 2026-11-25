<?php

namespace Tests\Feature;

use App\Jobs\SyncProductsJob;
use App\Models\Product;
use App\Models\SyncLog;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ProductCatalogSyncTest extends TestCase
{
    private function fakeMeliCatalog(array $pages, array $overrides = [], int $runs = 1): void
    {
        $scan = Http::sequence();
        for ($run = 0; $run < $runs; $run++) {
            foreach ($pages as $i => $ids) {
                $scan->push(['results' => $ids, 'scroll_id' => 'S' . $i]);
            }
            $scan->push(['results' => [], 'scroll_id' => 'END']);
        }

        Http::fake([
            'api.mercadolibre.com/users/123456789/items/search*' => $scan,
            'api.mercadolibre.com/items*' => function (Request $request) use ($overrides) {
                parse_str(parse_url($request->url(), PHP_URL_QUERY) ?? '', $query);

                return Http::response(array_map(fn (string $id) => $overrides[$id] ?? ['code' => 200, 'body' => [
                    'id'                 => $id,
                    'title'              => "Publicación {$id}",
                    'price'              => 50000,
                    'available_quantity' => 7,
                    'status'             => 'active',
                    'secure_thumbnail'   => "https://img.test/{$id}.jpg",
                ]], explode(',', $query['ids'] ?? '')));
            },
        ]);
    }

    private function meliItem(string $id, string $status): array
    {
        return ['code' => 200, 'body' => ['id' => $id, 'title' => "Publicación {$id}", 'price' => 50000, 'available_quantity' => 0, 'status' => $status]];
    }

    public function test_job_imports_every_page_of_the_scan_and_updates_existing_items(): void
    {
        [, $account] = $this->adminWithAccount();
        $existing = Product::factory()->create(['mercadolibre_account_id' => $account->id, 'meli_item_id' => 'MCO2', 'title' => 'Título viejo']);

        $firstPage = array_map(fn ($n) => 'MCO' . $n, range(1, 25));
        $this->fakeMeliCatalog([$firstPage, ['MCO26', 'MCO27']]);

        SyncProductsJob::dispatchSync($account);

        $this->assertSame(27, Product::where('mercadolibre_account_id', $account->id)->count());
        $this->assertSame('Publicación MCO2', $existing->fresh()->title);
        $this->assertSame(7, $existing->fresh()->stock);
        $this->assertSame('https://img.test/MCO27.jpg', Product::where('meli_item_id', 'MCO27')->value('thumbnail'));

        $log = SyncLog::latest('id')->first();
        $this->assertSame('success', $log->status);
        $this->assertSame(SyncProductsJob::LOG_PREFIX . ': 26 nuevas, 1 actualizadas', $log->message);

        Http::assertSent(fn (Request $request) => str_contains($request->url(), 'search_type=scan') && str_contains($request->url(), 'scroll_id=S0'));
    }

    public function test_job_updates_archived_products_without_restoring_them(): void
    {
        [, $account] = $this->adminWithAccount();
        $archived = Product::factory()->create(['mercadolibre_account_id' => $account->id, 'meli_item_id' => 'MCO9', 'status' => 'active']);
        $archived->delete();

        $this->fakeMeliCatalog([['MCO9', 'MCO10']], ['MCO9' => $this->meliItem('MCO9', 'paused')]);

        SyncProductsJob::dispatchSync($account);

        $this->assertSoftDeleted('products', ['id' => $archived->id, 'status' => 'paused']);
        $this->assertDatabaseHas('products', ['meli_item_id' => 'MCO10', 'deleted_at' => null]);
    }

    public function test_syncing_twice_never_duplicates_and_keeps_meli_status(): void
    {
        [, $account] = $this->adminWithAccount();
        $existing = Product::factory()->create(['mercadolibre_account_id' => $account->id, 'meli_item_id' => 'MCO55', 'status' => 'active', 'sku' => 'LOCAL-SKU']);

        $this->fakeMeliCatalog([['MCO55', 'MCO56']], ['MCO55' => $this->meliItem('MCO55', 'paused')], runs: 2);

        SyncProductsJob::dispatchSync($account);
        SyncProductsJob::dispatchSync($account);

        $this->assertSame(1, Product::where('meli_item_id', 'MCO55')->count());
        $this->assertSame(1, Product::where('meli_item_id', 'MCO56')->count());
        $this->assertSame('paused', $existing->fresh()->status);
        $this->assertSame('LOCAL-SKU', $existing->fresh()->sku);
    }

    public function test_items_missing_from_the_scan_get_their_status_from_meli(): void
    {
        [, $account] = $this->adminWithAccount();
        $closed  = Product::factory()->create(['mercadolibre_account_id' => $account->id, 'meli_item_id' => 'MCO70', 'status' => 'active']);
        $deleted = Product::factory()->create(['mercadolibre_account_id' => $account->id, 'meli_item_id' => 'MCO71', 'status' => 'active']);

        $this->fakeMeliCatalog([['MCO1']], [
            'MCO70' => $this->meliItem('MCO70', 'closed'),
            'MCO71' => ['code' => 404, 'body' => ['message' => 'Item not found', 'status' => 404]],
        ]);

        SyncProductsJob::dispatchSync($account);

        $this->assertSame('closed', $closed->fresh()->status);
        $this->assertSame('closed', $deleted->fresh()->status);
        $this->assertStringContainsString('1 nuevas, 2 actualizadas', SyncLog::latest('id')->value('message'));
    }

    public function test_item_button_updates_status_from_meli(): void
    {
        [$admin, $account] = $this->adminWithAccount();
        $product = Product::factory()->create(['mercadolibre_account_id' => $account->id, 'meli_item_id' => 'MCO300', 'status' => 'active']);
        $this->fakeMeliCatalog([], ['MCO300' => $this->meliItem('MCO300', 'paused')]);

        $this->actingAs($admin)->from(route('products.index'))->post(route('products.sync', $product))
            ->assertRedirect(route('products.index'))
            ->assertSessionHas('success', 'MCO300 sincronizado: estado actualizado de Activo a Pausado.');

        $this->assertSame('paused', $product->fresh()->status);
    }

    public function test_item_button_marks_deleted_listing_as_closed(): void
    {
        [$admin, $account] = $this->adminWithAccount();
        $product = Product::factory()->create(['mercadolibre_account_id' => $account->id, 'meli_item_id' => 'MCO301', 'status' => 'active']);
        $this->fakeMeliCatalog([], ['MCO301' => ['code' => 404, 'body' => ['message' => 'Item not found']]]);

        $this->actingAs($admin)->post(route('products.sync', $product))->assertSessionHas('success');

        $this->assertSame('closed', $product->fresh()->status);
    }

    public function test_item_button_reports_meli_errors_without_touching_the_product(): void
    {
        [$admin, $account] = $this->adminWithAccount();
        $product = Product::factory()->create(['mercadolibre_account_id' => $account->id, 'meli_item_id' => 'MCO302', 'status' => 'active', 'title' => 'Sin cambios']);
        Http::fake(['api.mercadolibre.com/items*' => Http::response(['message' => 'invalid access token', 'status' => 401], 401)]);

        $this->actingAs($admin)->from(route('products.index'))->post(route('products.sync', $product))
            ->assertRedirect(route('products.index'))
            ->assertSessionHasErrors('general');

        $this->assertSame('active', $product->fresh()->status);
        $this->assertSame('Sin cambios', $product->fresh()->title);
    }

    public function test_item_button_is_admin_only_and_scoped_to_the_active_account(): void
    {
        [$operator, $account] = $this->operatorWithAccount();
        $product = Product::factory()->create(['mercadolibre_account_id' => $account->id, 'meli_item_id' => 'MCO303']);
        $foreign = Product::factory()->create(['meli_item_id' => 'MCO304']);

        $this->actingAs($operator)->post(route('products.sync', $product))->assertForbidden();

        [$admin] = $this->adminWithAccount();
        $this->actingAs($admin)->post(route('products.sync', $foreign))->assertNotFound();
    }

    public function test_rejected_token_marks_the_import_as_failed_instead_of_empty(): void
    {
        [, $account] = $this->adminWithAccount();
        Http::fake([
            'api.mercadolibre.com/users/123456789/items/search*' => Http::response(['message' => 'invalid access token', 'status' => 401], 401),
        ]);

        try {
            SyncProductsJob::dispatchSync($account);
            $this->fail('La importación debía fallar con un token rechazado.');
        } catch (\App\Exceptions\MeliApiException) {
        }

        $log = SyncLog::latest('id')->first();
        $this->assertSame('error', $log->status);
        $this->assertStringContainsString('invalid access token', $log->message);
    }

    public function test_admin_button_queues_the_import_once(): void
    {
        Queue::fake();
        [$admin, $account] = $this->adminWithAccount();

        $this->actingAs($admin)->post(route('products.sync-all'))
            ->assertRedirect(route('products.index'))
            ->assertSessionHas('success');

        Queue::assertPushed(SyncProductsJob::class, fn (SyncProductsJob $job) => $job->account->is($account) && $job->syncLogId !== null);
        $this->assertDatabaseHas('sync_logs', ['mercadolibre_account_id' => $account->id, 'status' => 'pending']);

        $this->post(route('products.sync-all'))->assertSessionHas('error');
        Queue::assertPushed(SyncProductsJob::class, 1);
    }

    public function test_operator_cannot_start_the_import(): void
    {
        Queue::fake();
        [$operator] = $this->operatorWithAccount();

        $this->actingAs($operator)->post(route('products.sync-all'))->assertForbidden();

        Queue::assertNothingPushed();
    }

    public function test_index_shows_button_and_last_import_result(): void
    {
        [$admin, $account] = $this->adminWithAccount();
        SyncLog::create([
            'mercadolibre_account_id' => $account->id,
            'type'                    => 'products',
            'status'                  => 'success',
            'message'                 => SyncProductsJob::LOG_PREFIX . ': 12 nuevas, 3 actualizadas',
            'started_at'              => now()->subMinutes(5),
            'finished_at'             => now()->subMinutes(4),
        ]);

        $this->actingAs($admin)->get(route('products.index'))
            ->assertOk()
            ->assertSee('Sincronizar con Mercado Libre')
            ->assertSee('12 nuevas, 3 actualizadas');
    }

    public function test_index_shows_progress_and_disables_button_while_running(): void
    {
        [$admin, $account] = $this->adminWithAccount();
        SyncLog::create([
            'mercadolibre_account_id' => $account->id,
            'type'                    => 'products',
            'status'                  => 'running',
            'message'                 => SyncProductsJob::LOG_PREFIX . ' en curso',
            'started_at'              => now()->subMinute(),
        ]);

        $this->actingAs($admin)->get(route('products.index'))
            ->assertOk()
            ->assertSee('Sincronizando…')
            ->assertSee('Descargando publicaciones')
            ->assertSee('location.reload()', false);
    }
}
