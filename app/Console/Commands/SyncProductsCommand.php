<?php

namespace App\Console\Commands;

use App\Jobs\SyncProductsJob;
use App\Models\MercadolibreAccount;

class SyncProductsCommand extends AccountJobCommand
{
    protected $signature   = 'sync:products {--store= : ID de tienda específica} {--account= : ID de cuenta específica}';
    protected $description = 'Sincroniza productos desde Mercado Libre en todas las tiendas';

    protected function dispatchFor(MercadolibreAccount $account): void
    {
        SyncProductsJob::dispatch($account);
    }

    protected function label(): string
    {
        return 'Sincronización de productos';
    }
}
