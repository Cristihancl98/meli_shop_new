<?php

namespace App\Observers;

use App\Models\MercadolibreAccount;
use App\Services\TenantManager;

class MercadolibreAccountObserver
{
    public function __construct(private readonly TenantManager $tenantManager) {}

    public function saved(MercadolibreAccount $account): void
    {
        if ($account->wasChanged('meli_user_id') || $account->wasRecentlyCreated) {
            $this->tenantManager->registerMeliAccount($account->meli_user_id);
        }
    }
}
