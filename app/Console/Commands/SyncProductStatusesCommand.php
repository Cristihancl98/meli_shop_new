<?php

namespace App\Console\Commands;

use App\Jobs\SyncProductStatusesJob;
use App\Models\MercadolibreAccount;

class SyncProductStatusesCommand extends AccountJobCommand
{
    protected $signature   = 'sync:product-statuses {--store= : ID de tienda específica} {--account= : ID de cuenta específica}';
    protected $description = 'Actualiza el estado de las publicaciones locales según Mercado Libre';

    protected function dispatchFor(MercadolibreAccount $account): void
    {
        SyncProductStatusesJob::dispatch($account);
    }

    protected function label(): string
    {
        return 'Sincronización de estados';
    }
}
