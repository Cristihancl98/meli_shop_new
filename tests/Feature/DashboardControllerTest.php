<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\MercadolibreAccount;
use App\Models\Order;
use App\Models\Product;
use Tests\TestCase;

class DashboardControllerTest extends TestCase
{
    public function test_dashboard_returns_metrics(): void
    {
        $admin   = $this->createAdmin();
        $account = MercadolibreAccount::factory()->create(['user_id' => $admin->id]);
        Product::factory()->count(3)->active()->create(['mercadolibre_account_id' => $account->id]);

        $response = $this->getJson('/api/dashboard', $this->authHeaders($admin));

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    'products_active',
                    'products_paused',
                    'products_closed',
                    'orders_paid',
                    'orders_pending',
                    'total_customers',
                    'month_revenue',
                ],
            ]);
    }

    public function test_dashboard_requires_authentication(): void
    {
        $response = $this->getJson('/api/dashboard');

        $response->assertUnauthorized();
    }

    public function test_top_products_returns_list(): void
    {
        $admin   = $this->createAdmin();
        $account = MercadolibreAccount::factory()->create(['user_id' => $admin->id]);
        Product::factory()->count(5)->active()->create(['mercadolibre_account_id' => $account->id]);

        $response = $this->getJson('/api/dashboard/top-products', $this->authHeaders($admin));

        $response->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_sales_summary_returns_chart_data(): void
    {
        $admin = $this->createAdmin();
        MercadolibreAccount::factory()->create(['user_id' => $admin->id]);

        $response = $this->getJson('/api/dashboard/sales-summary', $this->authHeaders($admin));

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => ['daily', 'monthly'],
            ]);
    }

    public function test_alerts_returns_low_stock_and_pending_orders(): void
    {
        $admin   = $this->createAdmin();
        $account = MercadolibreAccount::factory()->create(['user_id' => $admin->id]);

        Product::factory()->create([
            'mercadolibre_account_id' => $account->id,
            'stock'                   => 2,
            'status'                  => 'active',
        ]);

        $customer = Customer::factory()->create(['mercadolibre_account_id' => $account->id]);
        Order::factory()->pending()->create([
            'mercadolibre_account_id' => $account->id,
            'customer_id'             => $customer->id,
        ]);

        $response = $this->getJson('/api/dashboard/alerts', $this->authHeaders($admin));

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => ['low_stock', 'pending_orders_count'],
            ]);
    }

    public function test_operator_can_view_dashboard(): void
    {
        $operator = $this->createOperator();
        MercadolibreAccount::factory()->create(['user_id' => $operator->id]);

        $response = $this->getJson('/api/dashboard', $this->authHeaders($operator));

        $response->assertOk();
    }
}
