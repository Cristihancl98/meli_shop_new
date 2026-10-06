<?php

namespace App\Console\Commands;

use App\Jobs\SyncDollarPriceJob;
use App\Models\Store;
use App\Services\TenantManager;
use Illuminate\Console\Command;

class SyncDollarPriceCommand extends Command
{
    protected $signature   = 'sync:dollar {--store= : ID de tienda específica}';
    protected $description = 'Actualiza el precio del dólar de referencia desde el catálogo MongoDB en todas las tiendas';

    public function handle(TenantManager $tenantManager): int
    {
        $tenantManager->eachActiveStore(function (Store $store) {
            SyncDollarPriceJob::dispatch();
            $this->info("[{$store->name}] Sincronización del dólar encolada.");
        }, $this->option('store') ? (int) $this->option('store') : null);

        return self::SUCCESS;
    }
}
