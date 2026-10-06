<?php

namespace Tests;

use App\Enums\SettingKey;
use App\Interfaces\AccountSettingRepositoryInterface;
use App\Interfaces\ExternalCatalogRepositoryInterface;
use App\Interfaces\StoreSettingRepositoryInterface;
use App\Models\MercadolibreAccount;
use App\Models\Role;
use App\Models\Store;
use App\Models\User;
use App\Services\TenantManager;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\DB;
use Tests\Fakes\FakeCatalogRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase {
        migrateFreshUsing as baseMigrateFreshUsing;
    }

    protected Store $store;

    protected FakeCatalogRepository $catalog;

    protected function setUp(): void
    {
        parent::setUp();

        $this->catalog = new FakeCatalogRepository();
        $this->app->instance(ExternalCatalogRepositoryInterface::class, $this->catalog);

        $this->store = $this->createStore('Tienda Test', 'TEST-CODE');
        app(TenantManager::class)->activate($this->store);
        $this->withSession([config('tenancy.session_key') => $this->store->id]);

        $this->seed(RoleSeeder::class);
    }

    protected function beforeRefreshingDatabase(): void
    {
        $sqlite = ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true];

        config([
            'database.default'              => 'tenant',
            'database.connections.tenant'   => $sqlite,
            'database.connections.landlord' => $sqlite,
            'tenancy.switch_connection'     => false,
        ]);

        DB::purge('tenant');
        DB::purge('landlord');
    }

    protected function afterRefreshingDatabase(): void
    {
        DB::connection('landlord')->setPdo(DB::connection('tenant')->getPdo());
        DB::connection('landlord')->setReadPdo(DB::connection('tenant')->getReadPdo());
    }

    protected function migrateFreshUsing(): array
    {
        return array_merge($this->baseMigrateFreshUsing(), [
            '--path' => ['database/migrations', config('tenancy.migrations_path')],
        ]);
    }

    protected function createStore(string $name, string $code, bool $active = true): Store
    {
        return Store::create([
            'name'            => $name,
            'connection_code' => $code,
            'database'        => 'meli_store_' . strtolower(preg_replace('/\W/', '_', $code)),
            'is_active'       => $active,
        ]);
    }

    protected function createAdmin(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('name', 'admin')->first());
        return $user;
    }

    protected function createOperator(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('name', 'operator')->first());
        return $user;
    }

    protected function adminWithAccount(): array
    {
        $admin = $this->createAdmin();

        return [$admin, MercadolibreAccount::factory()->create(['user_id' => $admin->id, 'meli_user_id' => '123456789'])];
    }

    protected function operatorWithAccount(): array
    {
        $operator = $this->createOperator();

        return [$operator, MercadolibreAccount::factory()->create(['user_id' => $operator->id])];
    }

    protected function setSettings(array $values, ?MercadolibreAccount $account = null): void
    {
        $stores   = app(StoreSettingRepositoryInterface::class);
        $accounts = app(AccountSettingRepositoryInterface::class);

        foreach ($values as $key => $value) {
            $settingKey = SettingKey::from($key);
            $settingKey->isAccountScoped()
                ? $accounts->put($account->id, $settingKey, $value)
                : $stores->put($settingKey, $value);
        }
    }

    protected function authHeaders(User $user): array
    {
        $token = JWTAuth::fromUser($user);
        return ['Authorization' => 'Bearer ' . $token, 'Accept' => 'application/json'];
    }
}
