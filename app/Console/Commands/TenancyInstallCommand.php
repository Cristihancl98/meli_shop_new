<?php

namespace App\Console\Commands;

use App\Services\StoreProvisioningService;
use Illuminate\Console\Command;

class TenancyInstallCommand extends Command
{
    protected $signature   = 'tenancy:install';
    protected $description = 'Crea la base de datos central (si no existe) y ejecuta sus migraciones';

    public function handle(StoreProvisioningService $provisioning): int
    {
        $provisioning->createCentralDatabase();

        $this->call('migrate', ['--database' => 'landlord', '--force' => true]);

        return self::SUCCESS;
    }
}
