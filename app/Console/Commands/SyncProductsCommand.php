<?php

namespace App\Console\Commands;

use App\Jobs\SyncProductsJob;
use App\Models\MercadolibreAccount;
use Illuminate\Console\Command;

class SyncProductsCommand extends Command
{
    protected $signature   = 'sync:products {--account= : ID de cuenta específica}';
    protected $description = 'Sincroniza productos desde Mercado Libre para todas las cuentas activas';

    public function handle(): int
    {
        $accounts = $this->getAccounts();

        if ($accounts->isEmpty()) {
            $this->warn('No hay cuentas de Mercado Libre activas.');
            return self::SUCCESS;
        }

        foreach ($accounts as $account) {
            SyncProductsJob::dispatch($account);
            $this->info("Sincronización de productos encolada para cuenta: {$account->meli_user_id}");
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
