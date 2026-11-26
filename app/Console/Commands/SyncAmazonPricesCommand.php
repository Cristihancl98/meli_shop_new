<?php

namespace App\Console\Commands;

use App\Jobs\SyncAmazonPricesJob;
use App\Models\MercadolibreAccount;

class SyncAmazonPricesCommand extends AccountJobCommand
{
    protected $signature   = 'sync:amazon-prices {--store= : ID de tienda específica} {--account= : ID de cuenta específica}';
    protected $description = 'Compara las publicaciones activas con el precio y estado de Amazon: sube precios, pausa las que ya no existen y marca bajadas de precio para revisión';

    protected function dispatchFor(MercadolibreAccount $account): void
    {
        SyncAmazonPricesJob::dispatch($account);
    }

    protected function label(): string
    {
        return 'Revisión de precios de Amazon';
    }
}
