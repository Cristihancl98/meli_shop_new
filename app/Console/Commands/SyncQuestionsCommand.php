<?php

namespace App\Console\Commands;

use App\Jobs\SyncQuestionsJob;
use App\Models\MercadolibreAccount;

class SyncQuestionsCommand extends AccountJobCommand
{
    protected $signature   = 'sync:questions {--store= : ID de tienda específica} {--account= : ID de cuenta específica}';
    protected $description = 'Descarga las preguntas preventa recientes desde Mercado Libre';

    protected function dispatchFor(MercadolibreAccount $account): void
    {
        SyncQuestionsJob::dispatch($account);
    }

    protected function label(): string
    {
        return 'Sincronización de preguntas';
    }
}
