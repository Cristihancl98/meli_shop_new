<?php

namespace App\Console\Commands;

use App\DTOs\CreateStoreDTO;
use App\Exceptions\BusinessRuleException;
use App\Services\StoreProvisioningService;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class StoreCreateCommand extends Command
{
    protected $signature = 'store:create
        {name : Nombre de la tienda}
        {--code= : Código de conexión (se genera si se omite)}
        {--database= : Nombre de la BD de la tienda (por defecto prefijo + nombre)}
        {--admin-email= : Correo del administrador inicial}
        {--admin-name=Administrador : Nombre del administrador inicial}
        {--admin-password= : Clave del administrador (se genera si se omite)}
        {--owner-name=} {--owner-email=} {--owner-phone=}';

    protected $description = 'Crea una tienda con su propia base de datos, migraciones y administrador inicial';

    public function handle(StoreProvisioningService $provisioning): int
    {
        $name       = (string) $this->argument('name');
        $code       = $this->option('code') ?: Str::upper(Str::random(8));
        $database   = $this->option('database') ?: config('tenancy.database_prefix') . Str::slug($name, '_');
        $adminEmail = $this->option('admin-email') ?: $this->ask('Correo del administrador inicial');
        $password   = $this->option('admin-password') ?: Str::password(14, symbols: false);

        try {
            $store = $provisioning->provision(new CreateStoreDTO(
                name:           $name,
                connectionCode: $code,
                database:       $database,
                adminName:      (string) $this->option('admin-name'),
                adminEmail:     (string) $adminEmail,
                adminPassword:  $password,
                ownerName:      $this->option('owner-name'),
                ownerEmail:     $this->option('owner-email'),
                ownerPhone:     $this->option('owner-phone'),
            ));
        } catch (BusinessRuleException $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        $this->info("Tienda creada (ID {$store->id}).");
        $this->table(['Campo', 'Valor'], [
            ['Código de conexión', $code],
            ['Base de datos', $database],
            ['Admin', $adminEmail],
            ['Clave admin', $this->option('admin-password') ? '(la indicada)' : $password],
        ]);

        return self::SUCCESS;
    }
}
