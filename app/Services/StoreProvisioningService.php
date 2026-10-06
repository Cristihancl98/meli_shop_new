<?php

namespace App\Services;

use App\DTOs\CreateStoreDTO;
use App\Exceptions\BusinessRuleException;
use App\Interfaces\StoreRepositoryInterface;
use App\Models\Store;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class StoreProvisioningService
{
    private const DATABASE_NAME_PATTERN = '/^[A-Za-z0-9_]{1,64}$/';

    public function __construct(
        private readonly StoreRepositoryInterface $storeRepository,
        private readonly TenantManager $tenantManager
    ) {}

    public function provision(CreateStoreDTO $dto): Store
    {
        if ($this->storeRepository->findActiveByCode($dto->connectionCode)) {
            throw new BusinessRuleException('El código de conexión ya está en uso.');
        }

        if ($this->storeRepository->isDatabaseInUse($dto->database)) {
            throw new BusinessRuleException('La base de datos ya está asignada a otra tienda.');
        }

        $this->createDatabase($dto->database);

        $store = $this->storeRepository->create([
            'name'            => $dto->name,
            'connection_code' => $dto->connectionCode,
            'database'        => $dto->database,
            'owner_name'      => $dto->ownerName,
            'owner_email'     => $dto->ownerEmail,
            'owner_phone'     => $dto->ownerPhone,
            'is_active'       => true,
        ]);

        $this->migrate($store);

        $this->tenantManager->runFor($store, function () use ($dto) {
            Artisan::call('db:seed', ['--class' => RoleSeeder::class, '--database' => 'tenant', '--force' => true]);
            Artisan::call('db:seed', ['--class' => CategorySeeder::class, '--database' => 'tenant', '--force' => true]);

            $admin = User::create([
                'name'              => $dto->adminName,
                'email'             => $dto->adminEmail,
                'password'          => $dto->adminPassword,
                'email_verified_at' => now(),
            ]);
            $admin->syncRole('admin');

            app(SettingService::class)->initializeDefaults();
        });

        return $store;
    }

    public function migrate(Store $store, bool $fresh = false): string
    {
        return $this->tenantManager->runFor($store, function () use ($fresh) {
            Artisan::call($fresh ? 'migrate:fresh' : 'migrate', [
                '--database' => 'tenant',
                '--path'     => config('tenancy.migrations_path'),
                '--force'    => true,
            ]);

            return Artisan::output();
        });
    }

    public function createDatabase(string $database): void
    {
        $this->assertValidDatabaseName($database);

        DB::connection('landlord')->statement(
            "CREATE DATABASE IF NOT EXISTS `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
        );
    }

    public function createCentralDatabase(): void
    {
        $database = (string) config('database.connections.landlord.database');
        $this->assertValidDatabaseName($database);

        config(['database.connections.landlord_server' => array_merge(config('database.connections.landlord'), ['database' => null])]);

        DB::connection('landlord_server')->statement(
            "CREATE DATABASE IF NOT EXISTS `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
        );

        DB::purge('landlord_server');
    }

    private function assertValidDatabaseName(string $database): void
    {
        if (!preg_match(self::DATABASE_NAME_PATTERN, $database)) {
            throw new BusinessRuleException('Nombre de base de datos inválido: solo letras, números y guion bajo.');
        }
    }
}
