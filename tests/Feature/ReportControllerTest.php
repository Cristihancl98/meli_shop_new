<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\MercadolibreAccount;
use App\Models\Order;
use App\Models\Product;
use Tests\TestCase;

class ReportControllerTest extends TestCase
{
    public function test_sales_report_accessible_by_admin(): void
    {
        $admin   = $this->createAdmin();
        $account = MercadolibreAccount::factory()->create(['user_id' => $admin->id]);
        $customer = Customer::factory()->create(['mercadolibre_account_id' => $account->id]);
        Order::factory()->paid()->count(3)->create([
            'mercadolibre_account_id' => $account->id,
            'customer_id'             => $customer->id,
        ]);

        $response = $this->getJson('/api/reports/sales', $this->authHeaders($admin));

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => ['rows', 'total_revenue', 'total_orders', 'paid_orders'],
            ]);
    }

    public function test_sales_report_accessible_by_operator(): void
    {
        $operator = $this->createOperator();
        MercadolibreAccount::factory()->create(['user_id' => $operator->id]);

        $response = $this->getJson('/api/reports/sales', $this->authHeaders($operator));

        $response->assertOk();
    }

    public function test_sales_report_requires_authentication(): void
    {
        $response = $this->getJson('/api/reports/sales');

        $response->assertUnauthorized();
    }

    public function test_top_products_report_returns_ranked_list(): void
    {
        $admin   = $this->createAdmin();
        $account = MercadolibreAccount::factory()->create(['user_id' => $admin->id]);
        Product::factory()->count(5)->create(['mercadolibre_account_id' => $account->id]);

        $response = $this->getJson('/api/reports/top-products', $this->authHeaders($admin));

        $response->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_top_customers_report_returns_ranked_list(): void
    {
        $admin   = $this->createAdmin();
        $account = MercadolibreAccount::factory()->create(['user_id' => $admin->id]);
        Customer::factory()->count(5)->create(['mercadolibre_account_id' => $account->id]);

        $response = $this->getJson('/api/reports/top-customers', $this->authHeaders($admin));

        $response->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_export_sales_only_for_admin(): void
    {
        $admin   = $this->createAdmin();
        MercadolibreAccount::factory()->create(['user_id' => $admin->id]);

        $response = $this->get('/api/reports/export/sales', $this->authHeaders($admin));

        $response->assertOk();
        $this->assertStringContainsString('spreadsheet', $response->headers->get('Content-Type') ?? '');
    }

    public function test_export_sales_forbidden_for_operator(): void
    {
        $operator = $this->createOperator();

        $response = $this->getJson('/api/reports/export/sales', $this->authHeaders($operator));

        $response->assertForbidden();
    }

    public function test_export_products_forbidden_for_operator(): void
    {
        $operator = $this->createOperator();

        $response = $this->getJson('/api/reports/export/products', $this->authHeaders($operator));

        $response->assertForbidden();
    }

    public function test_sales_report_filters_by_date_range(): void
    {
        $admin   = $this->createAdmin();
        $account = MercadolibreAccount::factory()->create(['user_id' => $admin->id]);
        $customer = Customer::factory()->create(['mercadolibre_account_id' => $account->id]);

        Order::factory()->paid()->create([
            'mercadolibre_account_id' => $account->id,
            'customer_id'             => $customer->id,
            'order_date'              => now()->subDays(10),
        ]);
        Order::factory()->paid()->create([
            'mercadolibre_account_id' => $account->id,
            'customer_id'             => $customer->id,
            'order_date'              => now()->subDays(60),
        ]);

        $response = $this->getJson(
            '/api/reports/sales?date_from=' . now()->subDays(30)->toDateString() . '&date_to=' . now()->toDateString(),
            $this->authHeaders($admin)
        );

        $response->assertOk();
        $rows = $response->json('data.rows');
        $this->assertCount(1, $rows);
    }
}
