<?php

namespace App\Console\Commands;

use App\Jobs\SyncOrdersJob;
use App\Models\MercadolibreAccount;

class SyncOrdersCommand extends AccountJobCommand
{
    protected $signature   = 'sync:orders {--store= : ID de tienda específica} {--account= : ID de cuenta específica} {--from= : Fecha inicio ISO8601}';
    protected $description = 'Sincroniza órdenes desde Mercado Libre en todas las tiendas';

    protected function dispatchFor(MercadolibreAccount $account): void
    {
        SyncOrdersJob::dispatch($account, $this->option('from'));
    }

    protected function label(): string
    {
        return 'Sincronización de órdenes';
    }
}
