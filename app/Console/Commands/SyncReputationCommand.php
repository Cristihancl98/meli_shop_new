<?php

namespace App\Console\Commands;

use App\Jobs\SyncReputationJob;
use App\Models\MercadolibreAccount;

class SyncReputationCommand extends AccountJobCommand
{
    protected $signature   = 'sync:reputation {--store= : ID de tienda específica} {--account= : ID de cuenta específica}';
    protected $description = 'Sincroniza la reputación de vendedor desde Mercado Libre';

    protected function dispatchFor(MercadolibreAccount $account): void
    {
        SyncReputationJob::dispatch($account);
    }

    protected function label(): string
    {
        return 'Sincronización de reputación';
    }
}
