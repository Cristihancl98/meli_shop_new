<?php

namespace Tests\Feature\Migrated;

use App\Models\StoreSetting;
use Tests\TestCase;

class SettingControllerTest extends TestCase
{
    public function test_store_settings_work_without_meli_account_and_use_legacy_defaults(): void
    {
        $admin = $this->createAdmin();

        $this->getJson('/api/settings', $this->authHeaders($admin))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.listing_type', 'gold_special')
            ->assertJsonPath('data.logistics_price', 5)
            ->assertJsonPath('data.shipping_price', 5)
            ->assertJsonPath('data.dollar_price', 5000)
            ->assertJsonPath('data.meli_commission', 16)
            ->assertJsonPath('data.iva', 0)
            ->assertJsonPath('data.manufacturing_days', 5)
            ->assertJsonPath('data.profit_ranges.0.percentage', 10)
            ->assertJsonPath('data.default_quantity', 1)
            ->assertJsonPath('data.sale_message_enabled', false);

        $this->putJson('/api/settings', ['iva' => 19], $this->authHeaders($admin))
            ->assertOk()
            ->assertJsonPath('data.iva', 19);
    }

    public function test_defaults_command_seeds_store_without_overwriting(): void
    {
        $this->setSettings(['dollar_price' => 4200]);

        $this->artisan('settings:defaults')->assertSuccessful();

        $this->assertSame('4200', StoreSetting::where('key', 'dollar_price')->value('value'));
        $this->assertSame(16, StoreSetting::count());
        $this->assertFalse(StoreSetting::where('key', 'sale_message')->value('is_enabled'));
    }

    public function test_update_persists_values_and_ranges_as_admin(): void
    {
        [$admin, $account] = $this->adminWithAccount();

        $this->putJson('/api/settings', [
            'iva'                  => 19,
            'dollar_price'         => 4100,
            'profit_ranges'        => [['from' => 0, 'to' => 100, 'percentage' => 30]],
            'sale_message'         => 'Gracias por tu compra',
            'sale_message_enabled' => true,
        ], $this->authHeaders($admin))
            ->assertOk()
            ->assertJsonPath('data.iva', 19)
            ->assertJsonPath('data.profit_ranges.0.percentage', 30)
            ->assertJsonPath('data.sale_message_enabled', true);

        $this->assertDatabaseHas('settings', ['key' => 'dollar_price', 'value' => '4100']);
    }

    public function test_update_is_forbidden_for_operator(): void
    {
        [$operator] = $this->operatorWithAccount();

        $this->putJson('/api/settings', ['iva' => 19], $this->authHeaders($operator))
            ->assertForbidden()
            ->assertJsonPath('success', false);
    }

    public function test_update_rejects_invalid_range(): void
    {
        [$admin] = $this->adminWithAccount();

        $this->putJson('/api/settings', [
            'weight_ranges' => [['from' => 10, 'to' => 5, 'price' => 3]],
        ], $this->authHeaders($admin))
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonValidationErrors(['weight_ranges.0.to']);
    }

    public function test_update_requires_at_least_one_setting(): void
    {
        [$admin] = $this->adminWithAccount();

        $this->putJson('/api/settings', ['unknown' => 1], $this->authHeaders($admin))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['settings']);
    }

    public function test_reputation_and_description_template(): void
    {
        [$admin, $account] = $this->adminWithAccount();
        $this->setSettings(['reputation' => 'green', 'description_template' => 'Envío internacional'], $account);

        $this->getJson('/api/settings/reputation', $this->authHeaders($admin))
            ->assertOk()
            ->assertJsonPath('data.reputation', 'green');

        $this->getJson('/api/settings/description-template', $this->authHeaders($admin))
            ->assertOk()
            ->assertJsonPath('data.description_template', 'Envío internacional');
    }

    public function test_reputation_requires_linked_account(): void
    {
        $this->getJson('/api/settings/reputation', $this->authHeaders($this->createAdmin()))
            ->assertUnprocessable()
            ->assertJsonPath('success', false);
    }

    public function test_requires_authentication(): void
    {
        $this->getJson('/api/settings')->assertUnauthorized();
    }
}
