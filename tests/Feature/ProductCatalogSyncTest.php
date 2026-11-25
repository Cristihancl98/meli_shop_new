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
    private function fakeMeliCatalog(array $pages): void
    {
        $scan = Http::sequence();
        foreach ($pages as $i => $ids) {
            $scan->push(['results' => $ids, 'scroll_id' => 'S' . $i]);
        }
        $scan->push(['results' => [], 'scroll_id' => 'END']);

        Http::fake([
            'api.mercadolibre.com/users/123456789/items/search*' => $scan,
            'api.mercadolibre.com/items*' => function (Request $request) {
                parse_str(parse_url($request->url(), PHP_URL_QUERY) ?? '', $query);

                return Http::response(array_map(fn (string $id) => ['code' => 200, 'body' => [
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

    public function test_job_respects_products_archived_in_the_panel(): void
    {
        [, $account] = $this->adminWithAccount();
        $archived = Product::factory()->create(['mercadolibre_account_id' => $account->id, 'meli_item_id' => 'MCO9', 'title' => 'Archivado']);
        $archived->delete();

        $this->fakeMeliCatalog([['MCO9', 'MCO10']]);

        SyncProductsJob::dispatchSync($account);

        $this->assertSoftDeleted('products', ['id' => $archived->id, 'title' => 'Archivado']);
        $this->assertDatabaseHas('products', ['meli_item_id' => 'MCO10']);
        $this->assertStringContainsString('1 omitidas', SyncLog::latest('id')->value('message'));
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
