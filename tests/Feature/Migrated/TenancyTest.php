<?php

namespace Tests\Feature\Migrated;

use App\Jobs\SyncQuestionsJob;
use App\Jobs\SyncReputationJob;
use App\Models\MercadolibreAccount;
use App\Models\User;
use App\Services\TenantManager;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Queue\Jobs\SyncJob;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class TenancyTest extends TestCase
{
    private function withoutStoreInSession(): void
    {
        $this->flushSession();
        app(TenantManager::class)->forget();
    }

    public function test_connection_code_is_the_first_screen(): void
    {
        $this->withoutStoreInSession();

        $this->get('/')->assertRedirect(route('connect.show'));
        $this->get('/login')->assertRedirect(route('connect.show'));
        $this->get(route('connect.show'))->assertOk()->assertSee('Código de conexión de la tienda');
    }

    public function test_valid_code_selects_store_and_shows_login(): void
    {
        $this->withoutStoreInSession();

        $this->post(route('connect.store'), ['connection_code' => 'TEST-CODE'])
            ->assertRedirect(route('login'))
            ->assertSessionHas('store_id', $this->store->id);

        $this->get('/login')->assertOk()->assertSee('Tienda Test');
    }

    public function test_invalid_or_disabled_code_is_rejected(): void
    {
        $this->createStore('Cerrada', 'OFF-CODE', active: false);
        $this->withoutStoreInSession();

        foreach (['NOPE', 'OFF-CODE'] as $code) {
            $this->from(route('connect.show'))
                ->post(route('connect.store'), ['connection_code' => $code])
                ->assertRedirect(route('connect.show'))
                ->assertSessionHasErrors('connection_code')
                ->assertSessionMissing('store_id');
        }
    }

    public function test_login_happens_inside_selected_store(): void
    {
        $user = User::factory()->create(['email' => 'ana@tienda.test', 'password' => 'secreto123']);
        $user->syncRole('admin');

        $this->post('/login', ['email' => 'ana@tienda.test', 'password' => 'secreto123'])
            ->assertRedirect('/dashboard');

        $this->assertAuthenticatedAs($user);
    }

    public function test_logout_keeps_store_and_change_store_forgets_it(): void
    {
        $this->actingAs($this->createAdmin());

        $this->post(route('logout'))->assertRedirect('/login');
        $this->get('/login')->assertOk();

        $this->post(route('connect.change'))->assertRedirect(route('connect.show'));
        $this->get('/login')->assertRedirect(route('connect.show'));
    }

    public function test_disabled_store_in_session_sends_back_to_connect(): void
    {
        $this->store->update(['is_active' => false]);
        app(TenantManager::class)->forget();

        $this->actingAs($this->createAdmin())->get('/dashboard')->assertRedirect(route('connect.show'));
    }

    public function test_api_login_requires_valid_connection_code(): void
    {
        User::factory()->create(['email' => 'api@tienda.test', 'password' => 'secreto123']);

        $this->postJson('/api/auth/login', ['email' => 'api@tienda.test', 'password' => 'secreto123'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('connection_code');

        $this->postJson('/api/auth/login', ['connection_code' => 'NOPE', 'email' => 'api@tienda.test', 'password' => 'secreto123'])
            ->assertUnprocessable();
    }

    public function test_api_token_carries_store_and_accepts_header_code(): void
    {
        User::factory()->create(['email' => 'api@tienda.test', 'password' => 'secreto123']);

        $token = $this->postJson('/api/auth/login', ['email' => 'api@tienda.test', 'password' => 'secreto123'], ['X-Store-Code' => 'TEST-CODE'])
            ->assertOk()
            ->json('data.token');

        $this->assertSame($this->store->id, JWTAuth::setToken($token)->getPayload()->get('store'));
    }

    public function test_token_of_disabled_store_is_rejected(): void
    {
        $headers = $this->authHeaders($this->createAdmin());
        $this->store->update(['is_active' => false]);
        app(TenantManager::class)->forget();

        $this->getJson('/api/auth/profile', $headers)->assertUnauthorized();
    }

    public function test_token_without_store_claim_is_rejected(): void
    {
        $admin = $this->createAdmin();
        app(TenantManager::class)->forget();
        $token = JWTAuth::fromUser($admin);

        $this->getJson('/api/auth/profile', ['Authorization' => "Bearer {$token}"])->assertUnauthorized();
    }

    public function test_connected_meli_account_is_registered_in_central_directory(): void
    {
        MercadolibreAccount::factory()->create(['meli_user_id' => '555000']);

        $this->assertDatabaseHas('store_meli_accounts', ['store_id' => $this->store->id, 'meli_user_id' => '555000'], 'landlord');
    }

    public function test_webhook_resolves_store_from_seller(): void
    {
        [, $account] = $this->adminWithAccount();
        app(TenantManager::class)->forget();

        Http::fake([
            'api.mercadolibre.com/questions/*' => Http::response([
                'id' => 9, 'item_id' => 'MCO1', 'text' => '¿Hay stock?', 'from' => ['id' => 1],
            ]),
            'api.mercadolibre.com/users/*' => Http::response(['nickname' => 'X']),
        ]);

        $this->postJson('/api/webhooks/mercadolibre', ['topic' => 'questions', 'resource' => '/questions/9', 'user_id' => $account->meli_user_id])
            ->assertOk();

        $this->assertSame($this->store->id, app(TenantManager::class)->current()?->id);
        $this->assertDatabaseHas('questions', ['meli_question_id' => '9']);
    }

    public function test_queued_jobs_remember_and_restore_their_store(): void
    {
        [, $account] = $this->adminWithAccount();
        Http::fake(['api.mercadolibre.com/users/*' => Http::response(['seller_reputation' => ['level_id' => '4_light_green']])]);

        SyncReputationJob::dispatch($account)->onConnection('database');

        $rawPayload = DB::connection('landlord')->table('jobs')->value('payload');
        $this->assertSame($this->store->id, json_decode($rawPayload, true)['store_id']);

        app(TenantManager::class)->forget();

        $job = new SyncJob(app(), $rawPayload, 'database', 'default');
        event(new JobProcessing('database', $job));
        $job->fire();

        $this->assertSame($this->store->id, app(TenantManager::class)->current()?->id);
        $this->assertDatabaseHas('account_settings', ['key' => 'reputation', 'value' => 'light_green']);
    }

    public function test_scheduled_commands_walk_only_active_stores(): void
    {
        Queue::fake();
        $this->adminWithAccount();
        $this->createStore('Inactiva', 'OFF', active: false);
        app(TenantManager::class)->forget();

        $this->artisan('sync:questions')->assertSuccessful();
        Queue::assertPushed(SyncQuestionsJob::class, 1);

        $this->artisan('sync:questions', ['--store' => 999])->expectsOutput('No hay tiendas activas.')->assertSuccessful();
    }

    public function test_store_create_rejects_duplicated_code_and_bad_database_name(): void
    {
        $this->artisan('store:create', ['name' => 'Otra', '--code' => 'TEST-CODE', '--admin-email' => 'a@b.c'])
            ->expectsOutput('El código de conexión ya está en uso.')
            ->assertFailed();

        $this->artisan('store:create', ['name' => 'Mala', '--code' => 'NEW', '--database' => 'bad-name;drop', '--admin-email' => 'a@b.c'])
            ->expectsOutput('Nombre de base de datos inválido: solo letras, números y guion bajo.')
            ->assertFailed();
    }
}
