<?php

namespace App\Console\Commands;

use App\Jobs\SyncCustomersJob;
use App\Models\MercadolibreAccount;

class SyncCustomersCommand extends AccountJobCommand
{
    protected $signature   = 'sync:customers {--store= : ID de tienda específica} {--account= : ID de cuenta específica}';
    protected $description = 'Sincroniza clientes desde Mercado Libre en todas las tiendas';

    protected function dispatchFor(MercadolibreAccount $account): void
    {
        SyncCustomersJob::dispatch($account);
    }

    protected function label(): string
    {
        return 'Sincronización de clientes';
    }
}
