<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\MercadolibreAccount;
use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OrderControllerTest extends TestCase
{
    public function test_index_returns_paginated_orders(): void
    {
        $admin   = $this->createAdmin();
        $account = MercadolibreAccount::factory()->create(['user_id' => $admin->id]);
        $customer = Customer::factory()->create(['mercadolibre_account_id' => $account->id]);
        Order::factory()->count(5)->create([
            'mercadolibre_account_id' => $account->id,
            'customer_id'             => $customer->id,
        ]);

        $response = $this->getJson('/api/orders', $this->authHeaders($admin));

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['success', 'data', 'message']);
    }

    public function test_index_requires_authentication(): void
    {
        $response = $this->getJson('/api/orders');

        $response->assertUnauthorized();
    }

    public function test_index_is_accessible_by_operator(): void
    {
        $operator = $this->createOperator();
        MercadolibreAccount::factory()->create(['user_id' => $operator->id]);

        $response = $this->getJson('/api/orders', $this->authHeaders($operator));

        $response->assertOk();
    }

    public function test_show_returns_order_detail(): void
    {
        $admin    = $this->createAdmin();
        $account  = MercadolibreAccount::factory()->create(['user_id' => $admin->id]);
        $customer = Customer::factory()->create(['mercadolibre_account_id' => $account->id]);
        $order    = Order::factory()->create([
            'mercadolibre_account_id' => $account->id,
            'customer_id'             => $customer->id,
        ]);

        $response = $this->getJson("/api/orders/{$order->id}", $this->authHeaders($admin));

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $order->id);
    }

    public function test_show_returns_404_for_missing_order(): void
    {
        $admin = $this->createAdmin();

        $response = $this->getJson('/api/orders/999999', $this->authHeaders($admin));

        $response->assertNotFound()
            ->assertJsonPath('success', false);
    }

    public function test_sync_dispatches_job_for_admin(): void
    {
        Http::fake([
            '*' => Http::response([
                'results' => [],
                'paging'  => ['total' => 0, 'limit' => 50, 'offset' => 0],
            ], 200),
        ]);

        $admin = $this->createAdmin();
        MercadolibreAccount::factory()->create(['user_id' => $admin->id]);

        $response = $this->postJson('/api/orders/sync', [], $this->authHeaders($admin));

        $response->assertOk()->assertJsonPath('success', true);
    }

    public function test_sync_returns_403_for_operator(): void
    {
        $operator = $this->createOperator();

        $response = $this->postJson('/api/orders/sync', [], $this->authHeaders($operator));

        $response->assertForbidden();
    }

    public function test_index_filters_by_status(): void
    {
        $admin    = $this->createAdmin();
        $account  = MercadolibreAccount::factory()->create(['user_id' => $admin->id]);
        $customer = Customer::factory()->create(['mercadolibre_account_id' => $account->id]);

        Order::factory()->count(3)->paid()->create([
            'mercadolibre_account_id' => $account->id,
            'customer_id'             => $customer->id,
        ]);
        Order::factory()->count(2)->pending()->create([
            'mercadolibre_account_id' => $account->id,
            'customer_id'             => $customer->id,
        ]);

        $response = $this->getJson('/api/orders?status=paid', $this->authHeaders($admin));

        $response->assertOk();
        $data = $response->json('data.data');
        $this->assertNotEmpty($data);
        foreach ($data as $order) {
            $this->assertEquals('paid', $order['status']);
        }
    }
}
