<?php

namespace App\Console\Commands;

use App\Jobs\SyncPostSaleMessagesJob;
use App\Models\MercadolibreAccount;

class SyncPostSaleMessagesCommand extends AccountJobCommand
{
    protected $signature   = 'sync:post-sale-messages {--store= : ID de tienda específica} {--account= : ID de cuenta específica} {--full : Descarga todas las conversaciones de todas las ventas}';
    protected $description = 'Sincroniza la mensajería posventa desde Mercado Libre';

    protected function dispatchFor(MercadolibreAccount $account): void
    {
        SyncPostSaleMessagesJob::dispatch($account, (bool) $this->option('full'));
    }

    protected function label(): string
    {
        return 'Sincronización de mensajes posventa';
    }
}
