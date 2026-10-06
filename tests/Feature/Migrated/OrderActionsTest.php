<?php

namespace Tests\Feature\Migrated;

use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OrderActionsTest extends TestCase
{
    public function test_admin_sets_final_price(): void
    {
        [$admin, $account] = $this->adminWithAccount();
        $order = Order::factory()->create(['mercadolibre_account_id' => $account->id]);

        $this->putJson("/api/orders/{$order->id}/final-price", ['final_price' => 125000.5], $this->authHeaders($admin))
            ->assertOk()
            ->assertJsonPath('data.final_price', '125000.50');
    }

    public function test_operator_cannot_set_final_price(): void
    {
        [$operator, $account] = $this->operatorWithAccount();
        $order = Order::factory()->create(['mercadolibre_account_id' => $account->id]);

        $this->putJson("/api/orders/{$order->id}/final-price", ['final_price' => 1], $this->authHeaders($operator))
            ->assertForbidden();
    }

    public function test_final_price_validation(): void
    {
        [$admin, $account] = $this->adminWithAccount();
        $order = Order::factory()->create(['mercadolibre_account_id' => $account->id]);

        $this->putJson("/api/orders/{$order->id}/final-price", ['final_price' => -5], $this->authHeaders($admin))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['final_price']);
    }

    public function test_downloads_shipping_label_pdf(): void
    {
        [$operator, $account] = $this->operatorWithAccount();
        $order = Order::factory()->create(['mercadolibre_account_id' => $account->id, 'shipping_id' => '4455']);

        Http::fake(['api.mercadolibre.com/shipment_labels*' => Http::response('%PDF-1.4 fake', 200, ['Content-Type' => 'application/pdf'])]);

        $response = $this->get("/api/orders/{$order->id}/shipping-label", $this->authHeaders($operator));

        $response->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertSame('%PDF-1.4 fake', $response->getContent());
    }

    public function test_shipping_label_unavailable_when_delivered(): void
    {
        [$admin, $account] = $this->adminWithAccount();
        $order = Order::factory()->create(['mercadolibre_account_id' => $account->id, 'shipping_id' => '4455']);

        Http::fake(['api.mercadolibre.com/shipment_labels*' => Http::response(['status' => 400], 400)]);

        $this->getJson("/api/orders/{$order->id}/shipping-label", $this->authHeaders($admin))->assertUnprocessable();
    }

    public function test_shipping_label_requires_shipment(): void
    {
        [$admin, $account] = $this->adminWithAccount();
        $order = Order::factory()->create(['mercadolibre_account_id' => $account->id, 'shipping_id' => null]);

        $this->getJson("/api/orders/{$order->id}/shipping-label", $this->authHeaders($admin))
            ->assertUnprocessable()
            ->assertJsonPath('message', 'La venta no tiene envío asociado.');
    }
}
