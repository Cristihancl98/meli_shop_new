<?php

namespace App\Console\Commands;

use App\Models\Store;
use App\Services\StoreProvisioningService;
use App\Services\TenantManager;
use Illuminate\Console\Command;

class TenantsMigrateCommand extends Command
{
    protected $signature   = 'tenants:migrate {--store= : ID de tienda específica} {--fresh : Borra y recrea las tablas} {--force}';
    protected $description = 'Ejecuta las migraciones de tienda en la base de datos de cada tienda activa';

    public function handle(TenantManager $tenantManager, StoreProvisioningService $provisioning): int
    {
        if ($this->option('fresh') && !$this->option('force') && !$this->confirm('Esto borra TODOS los datos de las tiendas seleccionadas. ¿Continuar?')) {
            return self::FAILURE;
        }

        $count = $tenantManager->eachActiveStore(function (Store $store) use ($provisioning) {
            $this->info("[{$store->name}] migrando...");
            $this->line(trim($provisioning->migrate($store, (bool) $this->option('fresh'))));
        }, $this->option('store') ? (int) $this->option('store') : null);

        if ($count === 0) {
            $this->warn('No hay tiendas activas.');
        }

        return self::SUCCESS;
    }
}
