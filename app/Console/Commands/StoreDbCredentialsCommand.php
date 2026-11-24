<?php

namespace App\Console\Commands;

use App\Exceptions\BusinessRuleException;
use App\Services\StoreProvisioningService;
use Illuminate\Console\Command;

class StoreDbCredentialsCommand extends Command
{
    protected $signature = 'store:db-credentials
        {store : ID o código de conexión de la tienda}
        {--db-username= : Usuario MySQL de la BD de la tienda}
        {--db-password= : Clave MySQL (si se omite se pregunta; vacía = TENANT_DB_PASSWORD)}
        {--global : Quita las credenciales propias y vuelve a usar las globales}';

    protected $description = 'Asigna el usuario y la clave MySQL de la base de datos de una tienda y prueba la conexión';

    public function handle(StoreProvisioningService $provisioning): int
    {
        $store = $provisioning->findStore((string) $this->argument('store'));

        if (!$store) {
            $this->error('Tienda no encontrada.');
            return self::FAILURE;
        }

        if ($this->option('global')) {
            $username = $password = null;
        } else {
            $username = $this->option('db-username') ?: $this->ask('Usuario MySQL', $store->database);
            $password = $this->option('db-password') ?: $this->secret('Clave MySQL (vacía = TENANT_DB_PASSWORD)');
        }

        try {
            $provisioning->updateDatabaseCredentials($store, $username ?: null, $password ?: null);
        } catch (BusinessRuleException $e) {
            $this->error($e->getMessage());
            $this->line('No se guardaron los cambios.');
            return self::FAILURE;
        }

        $this->info("[{$store->name}] conexión correcta, credenciales guardadas (usuario: " . ($username ?: 'global') . ').');

        return self::SUCCESS;
    }
}
