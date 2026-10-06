<?php

namespace Tests\Feature\Migrated;

use App\Models\Order;
use App\Models\Question;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MeliWebhookTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['services.mercadolibre.client_id' => '999']);
    }

    private function notify(string $topic, string $resource, string $appId = '999')
    {
        return $this->postJson('/api/webhooks/mercadolibre', [
            'topic'          => $topic,
            'resource'       => $resource,
            'user_id'        => '123456789',
            'application_id' => $appId,
        ]);
    }

    public function test_new_order_is_imported_with_items_notification_and_sale_message(): void
    {
        [, $account] = $this->adminWithAccount();
        $this->setSettings(['sale_message' => '¡Gracias por tu compra!']);
        \App\Models\StoreSetting::where('key', 'sale_message')->update(['is_enabled' => true]);

        Http::fake([
            'api.mercadolibre.com/orders/2000001' => Http::response([
                'id'           => 2000001,
                'pack_id'      => null,
                'status'       => 'paid',
                'total_amount' => 150000,
                'date_created' => '2026-10-01T10:00:00.000-05:00',
                'shipping'     => ['id' => 4455],
                'buyer'        => ['id' => 5551, 'nickname' => 'COMPRADOR1'],
                'payments'     => [['status' => 'approved']],
                'order_items'  => [[
                    'item'       => ['id' => 'MCO1', 'title' => 'Mouse', 'seller_sku' => 'B0MOUSE'],
                    'quantity'   => 2,
                    'unit_price' => 75000,
                ]],
            ]),
            'api.mercadolibre.com/items?ids=MCO1' => Http::response([[
                'code' => 200,
                'body' => ['id' => 'MCO1', 'pictures' => [['secure_url' => 'https://img.test/mouse.jpg']]],
            ]]),
            'api.mercadolibre.com/messages/packs/*' => Http::response(['id' => 'msg-sale', 'status' => 'available']),
        ]);

        $this->notify('orders_v2', '/orders/2000001')->assertOk();

        $order = Order::where('meli_order_id', '2000001')->firstOrFail();
        $this->assertSame('4455', $order->shipping_id);
        $this->assertDatabaseHas('order_items', ['order_id' => $order->id, 'sku' => 'B0MOUSE', 'thumbnail' => 'https://img.test/mouse.jpg', 'total_price' => 150000]);
        $this->assertDatabaseHas('meli_notifications', ['mercadolibre_account_id' => $account->id, 'type' => 'sale']);
        $this->assertDatabaseHas('post_sale_messages', ['order_id' => $order->id, 'text' => '¡Gracias por tu compra!', 'from_seller' => true]);
    }

    public function test_repeated_order_notification_does_not_duplicate(): void
    {
        [, $account] = $this->adminWithAccount();
        Order::factory()->create(['mercadolibre_account_id' => $account->id, 'meli_order_id' => '2000001']);

        Http::fake([
            'api.mercadolibre.com/orders/2000001' => Http::response([
                'id' => 2000001, 'status' => 'paid', 'total_amount' => 1, 'buyer' => ['id' => 1], 'order_items' => [],
            ]),
        ]);

        $this->notify('orders_v2', '/orders/2000001')->assertOk();

        $this->assertSame(1, Order::where('meli_order_id', '2000001')->count());
        $this->assertDatabaseCount('meli_notifications', 0);
    }

    public function test_question_notification_stores_question(): void
    {
        [, $account] = $this->adminWithAccount();

        Http::fake([
            'api.mercadolibre.com/questions/13201*' => Http::response([
                'id'           => 13201,
                'item_id'      => 'MCO9',
                'text'         => '¿Tiene garantía?',
                'status'       => 'UNANSWERED',
                'date_created' => '2026-10-01T10:00:00.000-04:00',
                'from'         => ['id' => 4321],
            ]),
            'api.mercadolibre.com/users/4321' => Http::response(['nickname' => 'PREGUNTON']),
        ]);

        $this->notify('questions', '/questions/13201')->assertOk();

        $this->assertDatabaseHas('questions', [
            'mercadolibre_account_id' => $account->id,
            'meli_question_id'        => '13201',
            'buyer_nickname'          => 'PREGUNTON',
            'seen'                    => false,
        ]);
        $this->assertDatabaseHas('meli_notifications', ['type' => 'pre_sale_question']);
    }

    public function test_message_notification_stores_post_sale_message(): void
    {
        [, $account] = $this->adminWithAccount();
        $order = Order::factory()->create(['mercadolibre_account_id' => $account->id, 'pack_id' => '2000777']);

        Http::fake([
            'api.mercadolibre.com/messages/abc123*' => Http::response(['messages' => [[
                'id'                => 'abc123',
                'text'              => '¿Cuándo llega?',
                'status'            => 'available',
                'from'              => ['user_id' => 5551],
                'message_date'      => ['created' => '2026-10-02T09:00:00.000Z'],
                'message_resources' => [['id' => '2000777', 'name' => 'packs']],
            ]]]),
        ]);

        $this->notify('messages', 'abc123')->assertOk();

        $this->assertDatabaseHas('post_sale_messages', [
            'order_id'        => $order->id,
            'meli_message_id' => 'abc123',
            'from_seller'     => false,
        ]);
        $this->assertDatabaseHas('meli_notifications', ['type' => 'post_sale_message']);
    }

    public function test_rejects_foreign_application(): void
    {
        $this->adminWithAccount();

        $this->notify('orders_v2', '/orders/1', '111')->assertStatus(400);
    }

    public function test_unknown_seller_is_acknowledged(): void
    {
        Http::fake();

        $this->notify('orders_v2', '/orders/1')->assertOk();

        Http::assertNothingSent();
    }
}
