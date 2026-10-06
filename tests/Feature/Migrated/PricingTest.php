<?php

namespace Tests\Feature\Migrated;

use App\Models\MercadolibreAccount;
use App\Services\PricingService;
use Tests\TestCase;

class PricingTest extends TestCase
{
    private function configurePricing(MercadolibreAccount $account): void
    {
        $this->setSettings([
            'amazon_commission'   => 10,
            'iva'                 => 19,
            'logistics_price'     => 5,
            'shipping_price'      => 3,
            'national_tax_ranges' => [['from' => 0, 'to' => 10, 'price' => 2]],
            'weight_ranges'       => [['from' => 0, 'to' => 10, 'price' => 4]],
            'profit_ranges'       => [['from' => 0, 'to' => 1000, 'percentage' => 30]],
            'meli_commission'     => 15,
            'dollar_price'        => 4000,
        ]);
    }

    public function test_service_applies_every_cost_in_order(): void
    {
        [, $account] = $this->adminWithAccount();
        $this->configurePricing($account);

        $result = app(PricingService::class)->calculate(100, 2);

        $this->assertEqualsWithDelta(10.0, $result->amazonCommission, 0.001);
        $this->assertEqualsWithDelta(20.9, $result->iva, 0.001);
        $this->assertEqualsWithDelta(2.0, $result->nationalTax, 0.001);
        $this->assertEqualsWithDelta(8.0, $result->usaShipping, 0.001);
        $this->assertEqualsWithDelta(44.67, $result->profit, 0.001);
        $this->assertEqualsWithDelta(29.0355, $result->meliCommission, 0.001);
        $this->assertSame(892000.0, $result->finalPrice);
    }

    public function test_service_returns_zero_for_zero_base_price(): void
    {
        [, $account] = $this->adminWithAccount();
        $this->configurePricing($account);

        $this->assertSame(0.0, app(PricingService::class)->calculate(0, 3)->finalPrice);
    }

    public function test_weight_outside_ranges_adds_no_shipping(): void
    {
        [, $account] = $this->adminWithAccount();
        $this->configurePricing($account);

        $result = app(PricingService::class)->calculate(100, 50);

        $this->assertSame(0.0, $result->usaShipping);
        $this->assertSame(0.0, $result->nationalTax);
    }

    public function test_calculate_endpoint_returns_breakdown(): void
    {
        [$admin, $account] = $this->adminWithAccount();
        $this->configurePricing($account);

        $this->getJson('/api/pricing/calculate?base_price=100&weight=2', $this->authHeaders($admin))
            ->assertOk()
            ->assertJsonPath('data.final_price', 892000)
            ->assertJsonPath('data.profit_percentage', 30);
    }

    public function test_calculate_endpoint_validates_input(): void
    {
        [$admin] = $this->adminWithAccount();

        $this->getJson('/api/pricing/calculate?base_price=-1', $this->authHeaders($admin))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['base_price', 'weight']);
    }
}
