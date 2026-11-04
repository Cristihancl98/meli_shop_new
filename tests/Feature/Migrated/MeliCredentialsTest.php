<?php

namespace Tests\Feature\Migrated;

use App\Models\StoreSetting;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MeliCredentialsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.mercadolibre.client_id'     => '',
            'services.mercadolibre.client_secret' => '',
            'services.mercadolibre.redirect_uri'  => '',
        ]);
    }

    private function saveCredentials(array $overrides = []): void
    {
        $this->setSettings(array_merge([
            'meli_client_id'     => '1234567890',
            'meli_client_secret' => 'store-secret',
            'meli_redirect_uri'  => 'https://tienda.test/meli/callback',
        ], $overrides));
    }

    public function test_admin_saves_credentials_with_encrypted_secret(): void
    {
        $this->actingAs($this->createAdmin());

        $this->put('/settings/meli-credentials', [
            'meli_client_id'     => '1234567890',
            'meli_client_secret' => 'store-secret',
            'meli_redirect_uri'  => 'https://tienda.test/meli/callback',
        ])->assertRedirect(route('settings.edit'));

        $raw = StoreSetting::where('key', 'meli_client_secret')->value('value');
        $this->assertNotSame('store-secret', $raw);
        $this->assertSame('store-secret', Crypt::decryptString($raw));
        $this->assertSame('1234567890', StoreSetting::where('key', 'meli_client_id')->value('value'));
    }

    public function test_blank_secret_keeps_the_stored_one(): void
    {
        $this->saveCredentials();
        $this->actingAs($this->createAdmin());

        $this->put('/settings/meli-credentials', ['meli_client_id' => '555', 'meli_client_secret' => ''])
            ->assertRedirect(route('settings.edit'));

        $this->assertSame('store-secret', Crypt::decryptString(StoreSetting::where('key', 'meli_client_secret')->value('value')));
        $this->assertSame('555', StoreSetting::where('key', 'meli_client_id')->value('value'));
    }

    public function test_redirect_uri_must_be_https(): void
    {
        $this->actingAs($this->createAdmin());

        $this->put('/settings/meli-credentials', ['meli_client_id' => '1', 'meli_redirect_uri' => 'http://localhost/meli/callback'])
            ->assertSessionHasErrors('meli_redirect_uri');
    }

    public function test_operator_cannot_update_credentials(): void
    {
        $this->actingAs($this->createOperator());

        $this->put('/settings/meli-credentials', ['meli_client_id' => '1'])->assertForbidden();
        $this->assertDatabaseMissing('settings', ['key' => 'meli_client_id']);
    }

    public function test_connect_without_credentials_redirects_to_settings(): void
    {
        $this->actingAs($this->createAdmin());

        $this->get('/meli/connect')
            ->assertRedirect(route('settings.edit'))
            ->assertSessionHas('error');
    }

    public function test_connect_uses_store_credentials(): void
    {
        $this->saveCredentials();
        $this->actingAs($this->createAdmin());

        $location = $this->get('/meli/connect')->assertRedirect()->headers->get('Location');

        $this->assertStringStartsWith('https://auth.mercadolibre.com.co/authorization?', $location);
        $this->assertStringContainsString('client_id=1234567890', $location);
        $this->assertStringContainsString('redirect_uri=' . urlencode('https://tienda.test/meli/callback'), $location);
    }

    public function test_callback_exchanges_code_with_store_credentials(): void
    {
        $this->saveCredentials();
        $admin = $this->createAdmin();
        $this->actingAs($admin);

        Http::fake([
            'api.mercadolibre.com/oauth/token' => Http::response([
                'access_token' => 'APP_USR-token', 'refresh_token' => 'TG-refresh', 'user_id' => 777, 'expires_in' => 21600,
            ]),
            'api.mercadolibre.com/users/me' => Http::response(['nickname' => 'MI_TIENDA', 'email' => 'tienda@test.co']),
        ]);

        $this->get('/meli/callback?code=TG-code')->assertRedirect(route('accounts.index'));

        Http::assertSent(fn ($request) => $request->url() === 'https://api.mercadolibre.com/oauth/token'
            && $request['client_id'] === '1234567890'
            && $request['client_secret'] === 'store-secret'
            && $request['redirect_uri'] === 'https://tienda.test/meli/callback'
            && $request['code'] === 'TG-code');

        $this->assertDatabaseHas('mercadolibre_accounts', ['user_id' => $admin->id, 'meli_user_id' => '777', 'nickname' => 'MI_TIENDA']);
    }

    public function test_env_values_are_used_as_fallback(): void
    {
        config([
            'services.mercadolibre.client_id'     => '42',
            'services.mercadolibre.client_secret' => 'env-secret',
        ]);
        $this->actingAs($this->createAdmin());

        $location = $this->get('/meli/connect')->headers->get('Location');

        $this->assertStringContainsString('client_id=42', $location);
        $this->assertStringContainsString('redirect_uri=' . urlencode(route('meli.callback')), $location);
    }

    public function test_settings_page_shows_credentials_card_to_admin_only(): void
    {
        $this->saveCredentials();

        $this->actingAs($this->createAdmin())->get('/settings')
            ->assertOk()
            ->assertSee('Aplicación de Mercado Libre')
            ->assertSee('1234567890')
            ->assertDontSee('store-secret');

        $this->actingAs($this->createOperator())->get('/settings')
            ->assertOk()
            ->assertDontSee('Aplicación de Mercado Libre');
    }

    public function test_webhook_validates_against_store_client_id(): void
    {
        $this->saveCredentials();
        $this->adminWithAccount();
        Http::fake();

        $payload = ['topic' => 'unhandled_topic', 'resource' => '/x/1', 'user_id' => '123456789'];

        $this->postJson('/api/webhooks/mercadolibre', $payload + ['application_id' => '999'])->assertStatus(400);
        $this->postJson('/api/webhooks/mercadolibre', $payload + ['application_id' => '1234567890'])->assertOk();
    }
}
