<?php

namespace App\Console\Commands;

use App\Jobs\SyncOrdersJob;
use App\Models\MercadolibreAccount;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SyncOrdersCommand extends Command
{
    protected $signature   = 'sync:orders {--account= : ID de cuenta específica} {--from= : Fecha inicio ISO8601}';
    protected $description = 'Sincroniza órdenes desde Mercado Libre para todas las cuentas activas';

    public function handle(): int
    {
        $accounts = $this->getAccounts();

        if ($accounts->isEmpty()) {
            $this->warn('No hay cuentas de Mercado Libre activas.');
            return self::SUCCESS;
        }

        $dateFrom = $this->option('from') ?? Carbon::now()->subHours(24)->toIso8601String();

        foreach ($accounts as $account) {
            SyncOrdersJob::dispatch($account, $dateFrom);
            $this->info("Sincronización de órdenes encolada para cuenta: {$account->meli_user_id}");
        }

        return self::SUCCESS;
    }

    private function getAccounts()
    {
        $query = MercadolibreAccount::query();

        if ($accountId = $this->option('account')) {
            $query->where('id', $accountId);
        }

        return $query->get();
    }
}
