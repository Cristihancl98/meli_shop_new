<?php

namespace Tests\Feature\Migrated;

use App\Models\Customer;
use App\Models\Order;
use App\Models\PostSaleMessage;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PostSaleControllerTest extends TestCase
{
    public function test_lists_conversations_grouped_by_order(): void
    {
        [$admin, $account] = $this->adminWithAccount();
        $order = Order::factory()->create(['mercadolibre_account_id' => $account->id, 'pack_id' => '2000555']);
        PostSaleMessage::factory()->count(2)->create([
            'mercadolibre_account_id' => $account->id,
            'order_id'                => $order->id,
            'pack_id'                 => '2000555',
        ]);
        Order::factory()->create(['mercadolibre_account_id' => $account->id]);

        $this->getJson('/api/post-sale/conversations', $this->authHeaders($admin))
            ->assertOk()
            ->assertJsonPath('data.total', 1)
            ->assertJsonCount(2, 'data.data.0.post_sale_messages');
    }

    public function test_reply_sends_message_to_buyer(): void
    {
        [$operator, $account] = $this->operatorWithAccount();
        $customer = Customer::factory()->create(['mercadolibre_account_id' => $account->id, 'meli_customer_id' => '5551']);
        $order    = Order::factory()->create([
            'mercadolibre_account_id' => $account->id,
            'customer_id'             => $customer->id,
            'meli_order_id'           => '2000999',
            'pack_id'                 => null,
        ]);

        Http::fake(['api.mercadolibre.com/messages/packs/*' => Http::response(['id' => 'msg-1', 'status' => 'available'])]);

        $this->postJson("/api/post-sale/conversations/{$order->id}/messages", ['text' => 'Tu pedido va en camino'], $this->authHeaders($operator))
            ->assertCreated()
            ->assertJsonPath('data.from_seller', true);

        Http::assertSent(fn ($request) => str_contains($request->url(), "/messages/packs/2000999/sellers/{$account->meli_user_id}")
            && $request['to']['user_id'] === '5551');

        $this->assertDatabaseHas('post_sale_messages', ['order_id' => $order->id, 'meli_message_id' => 'msg-1', 'sent_by' => $operator->id]);
    }

    public function test_reply_validates_length(): void
    {
        [$admin, $account] = $this->adminWithAccount();
        $order = Order::factory()->create(['mercadolibre_account_id' => $account->id]);

        $this->postJson("/api/post-sale/conversations/{$order->id}/messages", ['text' => str_repeat('a', 351)], $this->authHeaders($admin))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['text']);
    }

    public function test_reply_returns_404_for_foreign_order(): void
    {
        [$admin] = $this->adminWithAccount();
        $foreign = Order::factory()->create();

        $this->postJson("/api/post-sale/conversations/{$foreign->id}/messages", ['text' => 'Hola'], $this->authHeaders($admin))
            ->assertNotFound();
    }
}
