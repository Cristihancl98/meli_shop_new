<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\MercadolibreAccount;
use App\Models\Order;
use Tests\TestCase;

class CustomerControllerTest extends TestCase
{
    public function test_index_returns_paginated_customers(): void
    {
        $admin   = $this->createAdmin();
        $account = MercadolibreAccount::factory()->create(['user_id' => $admin->id]);
        Customer::factory()->count(5)->create(['mercadolibre_account_id' => $account->id]);

        $response = $this->getJson('/api/customers', $this->authHeaders($admin));

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['success', 'data', 'message']);
    }

    public function test_index_requires_authentication(): void
    {
        $response = $this->getJson('/api/customers');

        $response->assertUnauthorized();
    }

    public function test_index_is_accessible_by_operator(): void
    {
        $operator = $this->createOperator();
        MercadolibreAccount::factory()->create(['user_id' => $operator->id]);

        $response = $this->getJson('/api/customers', $this->authHeaders($operator));

        $response->assertOk();
    }

    public function test_show_returns_customer_detail(): void
    {
        $admin    = $this->createAdmin();
        $account  = MercadolibreAccount::factory()->create(['user_id' => $admin->id]);
        $customer = Customer::factory()->create(['mercadolibre_account_id' => $account->id]);

        $response = $this->getJson("/api/customers/{$customer->id}", $this->authHeaders($admin));

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $customer->id);
    }

    public function test_show_returns_404_for_missing_customer(): void
    {
        $admin = $this->createAdmin();

        $response = $this->getJson('/api/customers/999999', $this->authHeaders($admin));

        $response->assertNotFound()
            ->assertJsonPath('success', false);
    }

    public function test_get_customer_orders(): void
    {
        $admin    = $this->createAdmin();
        $account  = MercadolibreAccount::factory()->create(['user_id' => $admin->id]);
        $customer = Customer::factory()->create(['mercadolibre_account_id' => $account->id]);
        Order::factory()->count(3)->create([
            'mercadolibre_account_id' => $account->id,
            'customer_id'             => $customer->id,
        ]);

        $response = $this->getJson("/api/customers/{$customer->id}/orders", $this->authHeaders($admin));

        $response->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_index_filters_by_search(): void
    {
        $admin    = $this->createAdmin();
        $account  = MercadolibreAccount::factory()->create(['user_id' => $admin->id]);
        Customer::factory()->create([
            'mercadolibre_account_id' => $account->id,
            'name'                    => 'Carlos Martinez',
            'nickname'                => 'carlosmtz',
        ]);
        Customer::factory()->create([
            'mercadolibre_account_id' => $account->id,
            'name'                    => 'Ana Lopez',
            'nickname'                => 'analopez',
        ]);

        $response = $this->getJson('/api/customers?search=Carlos', $this->authHeaders($admin));

        $response->assertOk();
        $data = $response->json('data.data');
        $this->assertCount(1, $data);
        $this->assertStringContainsString('Carlos', $data[0]['name']);
    }
}
