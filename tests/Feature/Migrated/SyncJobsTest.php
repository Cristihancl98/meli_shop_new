<?php

namespace Tests\Feature\Migrated;

use App\Enums\SettingKey;
use App\Interfaces\ExternalCatalogRepositoryInterface;
use App\Jobs\RefreshMeliTokenJob;
use App\Jobs\SyncDollarPriceJob;
use App\Jobs\SyncOrdersJob;
use App\Jobs\SyncPostSaleMessagesJob;
use App\Jobs\SyncProductStatusesJob;
use App\Jobs\SyncProductsJob;
use App\Jobs\SyncQuestionsJob;
use App\Jobs\SyncReputationJob;
use App\Models\MercadolibreAccount;
use App\Models\Order;
use App\Models\Product;
use App\Models\Question;
use App\Services\SettingService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class SyncJobsTest extends TestCase
{
    public function test_refresh_token_job_stores_new_tokens(): void
    {
        [, $account] = $this->adminWithAccount();

        Http::fake(['api.mercadolibre.com/oauth/token' => Http::response([
            'access_token' => 'APP_USR-new', 'refresh_token' => 'TG-new', 'expires_in' => 21600,
        ])]);

        RefreshMeliTokenJob::dispatchSync($account);

        $account->refresh();
        $this->assertSame('APP_USR-new', $account->access_token);
        $this->assertSame('TG-new', $account->refresh_token);
    }

    public function test_reputation_job_stores_color(): void
    {
        [, $account] = $this->adminWithAccount();

        Http::fake(['api.mercadolibre.com/users/123456789' => Http::response(['seller_reputation' => ['level_id' => '5_green']])]);

        SyncReputationJob::dispatchSync($account);

        $this->assertSame('green', app(SettingService::class)->reputation($account)['reputation']);
    }

    public function test_dollar_job_updates_store_reference_dollar(): void
    {
        $this->mock(ExternalCatalogRepositoryInterface::class)->shouldReceive('currentDollarPrice')->andReturn(4123.5);

        SyncDollarPriceJob::dispatchSync();

        $this->assertSame(4123.5, app(SettingService::class)->get(SettingKey::CurrentDollarPrice));
    }

    public function test_product_statuses_job_updates_from_multiget(): void
    {
        [, $account] = $this->adminWithAccount();
        $active = Product::factory()->active()->create(['mercadolibre_account_id' => $account->id, 'meli_item_id' => 'MCO1']);
        $review = Product::factory()->active()->create(['mercadolibre_account_id' => $account->id, 'meli_item_id' => 'MCO2']);

        Http::fake(['api.mercadolibre.com/items?ids=*' => Http::response([
            ['code' => 200, 'body' => ['id' => 'MCO1', 'status' => 'paused', 'sold_quantity' => 4]],
            ['code' => 200, 'body' => ['id' => 'MCO2', 'status' => 'under_review', 'sold_quantity' => 0]],
        ])]);

        SyncProductStatusesJob::dispatchSync($account);

        $this->assertSame('paused', $active->fresh()->status);
        $this->assertSame(4, $active->fresh()->sold_quantity);
        $this->assertSame('under_review', $review->fresh()->status);
    }

    public function test_products_job_imports_items_with_sku_and_pictures(): void
    {
        [, $account] = $this->adminWithAccount();

        Http::fake([
            'api.mercadolibre.com/users/123456789/items/search*' => Http::response(['results' => ['MCO77'], 'paging' => ['total' => 1]]),
            'api.mercadolibre.com/items/MCO77'                   => Http::response([
                'id'            => 'MCO77',
                'title'         => 'Lámpara',
                'price'         => 99000,
                'status'        => 'active',
                'sold_quantity' => 3,
                'attributes'    => [['id' => 'SELLER_SKU', 'value_name' => 'B0LAMP']],
                'pictures'      => [['secure_url' => 'https://img.test/lamp.jpg']],
            ]),
        ]);

        SyncProductsJob::dispatchSync($account);

        $this->assertDatabaseHas('products', ['meli_item_id' => 'MCO77', 'sku' => 'B0LAMP', 'sold_quantity' => 3]);
        $this->assertSame(['https://img.test/lamp.jpg'], Product::where('meli_item_id', 'MCO77')->first()->pictures);
    }

    public function test_questions_job_inserts_new_and_completes_answers(): void
    {
        [, $account] = $this->adminWithAccount();
        $pending = Question::factory()->create(['mercadolibre_account_id' => $account->id, 'meli_question_id' => '501']);

        Http::fake([
            'api.mercadolibre.com/questions/search*' => Http::response(['questions' => [
                ['id' => 502, 'item_id' => 'MCO1', 'text' => '¿Envío gratis?', 'status' => 'UNANSWERED', 'date_created' => '2026-10-01T10:00:00Z', 'from' => ['id' => 77]],
                ['id' => 501, 'item_id' => 'MCO1', 'text' => 'Vieja', 'status' => 'ANSWERED', 'from' => ['id' => 78],
                 'answer' => ['text' => 'Sí', 'status' => 'ACTIVE', 'date_created' => '2026-10-01T11:00:00Z']],
            ]]),
            'api.mercadolibre.com/users/*' => Http::response(['nickname' => 'NICK']),
        ]);

        SyncQuestionsJob::dispatchSync($account);

        $this->assertDatabaseHas('questions', ['meli_question_id' => '502', 'buyer_nickname' => 'NICK']);
        $this->assertSame('Sí', $pending->fresh()->answer);
    }

    public function test_post_sale_job_imports_unread_conversations(): void
    {
        [, $account] = $this->adminWithAccount();
        $order = Order::factory()->create(['mercadolibre_account_id' => $account->id, 'pack_id' => '2000888']);

        Http::fake([
            'api.mercadolibre.com/messages/unread*' => Http::response(['results' => [
                ['resource' => '/packs/2000888/sellers/123456789', 'count' => 1],
            ]]),
            'api.mercadolibre.com/messages/packs/2000888/*' => Http::response(['messages' => [
                ['id' => 'm1', 'text' => 'Hola', 'from' => ['user_id' => 555], 'message_date' => ['created' => '2026-10-01T10:00:00Z']],
                ['id' => 'm2', 'text' => 'Buen día', 'from' => ['user_id' => 123456789], 'message_date' => ['created' => '2026-10-01T10:05:00Z']],
            ]]),
        ]);

        SyncPostSaleMessagesJob::dispatchSync($account);
        SyncPostSaleMessagesJob::dispatchSync($account);

        $this->assertDatabaseCount('post_sale_messages', 2);
        $this->assertDatabaseHas('post_sale_messages', ['meli_message_id' => 'm2', 'from_seller' => true, 'order_id' => $order->id]);
    }

    public function test_post_sale_full_download_walks_every_order(): void
    {
        [, $account] = $this->adminWithAccount();
        Order::factory()->create(['mercadolibre_account_id' => $account->id, 'meli_order_id' => '3001', 'pack_id' => null]);

        Http::fake(['api.mercadolibre.com/messages/packs/3001/*' => Http::response(['messages' => [
            ['id' => 'x1', 'text' => 'Gracias', 'from' => ['user_id' => 9], 'message_date' => ['created' => '2026-10-01T10:00:00Z']],
        ]])]);

        SyncPostSaleMessagesJob::dispatchSync($account, true);

        $this->assertDatabaseHas('post_sale_messages', ['pack_id' => '3001', 'meli_message_id' => 'x1']);
    }

    public function test_orders_job_imports_and_records_sync_date(): void
    {
        [, $account] = $this->adminWithAccount();

        Http::fake([
            'api.mercadolibre.com/orders/search*' => Http::response([
                'results' => [[
                    'id' => 4001, 'status' => 'paid', 'total_amount' => 50000, 'date_created' => '2026-10-01T10:00:00Z',
                    'buyer' => ['id' => 66, 'nickname' => 'BUYER'], 'order_items' => [],
                ]],
                'paging' => ['total' => 1],
            ]),
        ]);

        SyncOrdersJob::dispatchSync($account);

        $this->assertDatabaseHas('orders', ['meli_order_id' => '4001', 'mercadolibre_account_id' => $account->id]);
        $this->assertNotNull(app(SettingService::class)->get(SettingKey::OrdersSyncedAt, $account));
    }

    public function test_commands_dispatch_jobs_for_active_accounts(): void
    {
        Queue::fake();
        $this->adminWithAccount();
        MercadolibreAccount::factory()->create(['is_active' => false]);

        $this->artisan('meli:refresh-tokens')->assertSuccessful();
        $this->artisan('sync:reputation')->assertSuccessful();
        $this->artisan('sync:product-statuses')->assertSuccessful();
        $this->artisan('sync:questions')->assertSuccessful();
        $this->artisan('sync:post-sale-messages --full')->assertSuccessful();
        $this->artisan('sync:dollar')->assertSuccessful();

        Queue::assertPushed(RefreshMeliTokenJob::class, 1);
        Queue::assertPushed(SyncReputationJob::class, 1);
        Queue::assertPushed(SyncProductStatusesJob::class, 1);
        Queue::assertPushed(SyncQuestionsJob::class, 1);
        Queue::assertPushed(SyncPostSaleMessagesJob::class, fn ($job) => $job->full === true);
        Queue::assertPushed(SyncDollarPriceJob::class, 1);
    }
}
