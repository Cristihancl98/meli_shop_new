<?php

namespace App\Console\Commands;

use App\Jobs\CalculateStatisticsJob;
use App\Models\MercadolibreAccount;
use Illuminate\Console\Command;

class CalculateStatisticsCommand extends Command
{
    protected $signature   = 'calculate:statistics {--account= : ID de cuenta específica}';
    protected $description = 'Recalcula estadísticas de ventas y productos para todas las cuentas activas';

    public function handle(): int
    {
        $accounts = $this->getAccounts();

        if ($accounts->isEmpty()) {
            $this->warn('No hay cuentas de Mercado Libre activas.');
            return self::SUCCESS;
        }

        foreach ($accounts as $account) {
            CalculateStatisticsJob::dispatch($account);
            $this->info("Cálculo de estadísticas encolado para cuenta: {$account->meli_user_id}");
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
