<?php

namespace App\Console\Commands;

use App\Models\Store;
use App\Services\SettingService;
use App\Services\TenantManager;
use Illuminate\Console\Command;

class ApplyDefaultSettingsCommand extends Command
{
    protected $signature   = 'settings:defaults {--store= : ID de tienda específica}';
    protected $description = 'Inserta la configuración por defecto (config/store_defaults.php) en las tiendas que no la tengan, sin sobrescribir valores existentes';

    public function handle(TenantManager $tenantManager, SettingService $settingService): int
    {
        $tenantManager->eachActiveStore(function (Store $store) use ($settingService) {
            $created = $settingService->initializeDefaults();
            $this->info("[{$store->name}] {$created} configuración(es) agregada(s).");
        }, $this->option('store') ? (int) $this->option('store') : null);

        return self::SUCCESS;
    }
}
