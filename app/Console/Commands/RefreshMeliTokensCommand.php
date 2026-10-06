<?php

namespace App\Console\Commands;

use App\Jobs\RefreshMeliTokenJob;
use App\Models\MercadolibreAccount;

class RefreshMeliTokensCommand extends AccountJobCommand
{
    protected $signature   = 'meli:refresh-tokens {--store= : ID de tienda específica} {--account= : ID de cuenta específica}';
    protected $description = 'Renueva los tokens OAuth de las cuentas de Mercado Libre';

    protected function dispatchFor(MercadolibreAccount $account): void
    {
        RefreshMeliTokenJob::dispatch($account);
    }

    protected function label(): string
    {
        return 'Renovación de token';
    }
}
