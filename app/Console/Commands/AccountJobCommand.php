<?php

namespace App\Console\Commands;

use App\Models\MercadolibreAccount;
use App\Models\Store;
use App\Services\TenantManager;
use Illuminate\Console\Command;

abstract class AccountJobCommand extends Command
{
    abstract protected function dispatchFor(MercadolibreAccount $account): void;

    abstract protected function label(): string;

    public function handle(TenantManager $tenantManager): int
    {
        $storeId = $this->option('store') ? (int) $this->option('store') : null;

        $stores = $tenantManager->eachActiveStore(function (Store $store) {
            $accounts = MercadolibreAccount::where('is_active', true)
                ->when($this->option('account'), fn ($q, $id) => $q->where('id', $id))
                ->get();

            if ($accounts->isEmpty()) {
                $this->warn("[{$store->name}] No hay cuentas de Mercado Libre activas.");
                return;
            }

            foreach ($accounts as $account) {
                $this->dispatchFor($account);
                $this->info("[{$store->name}] {$this->label()} encolada para cuenta: {$account->meli_user_id}");
            }
        }, $storeId);

        if ($stores === 0) {
            $this->warn('No hay tiendas activas.');
        }

        return self::SUCCESS;
    }
}
