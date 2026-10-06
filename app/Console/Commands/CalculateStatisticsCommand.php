<?php

namespace App\Console\Commands;

use App\Jobs\CalculateStatisticsJob;
use App\Models\MercadolibreAccount;

class CalculateStatisticsCommand extends AccountJobCommand
{
    protected $signature   = 'calculate:statistics {--store= : ID de tienda específica} {--account= : ID de cuenta específica}';
    protected $description = 'Recalcula estadísticas de ventas y productos en todas las tiendas';

    protected function dispatchFor(MercadolibreAccount $account): void
    {
        CalculateStatisticsJob::dispatch($account);
    }

    protected function label(): string
    {
        return 'Cálculo de estadísticas';
    }
}
