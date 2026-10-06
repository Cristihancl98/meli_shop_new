<?php

namespace App\Traits;

use App\Interfaces\MercadolibreAccountRepositoryInterface;
use App\Models\MercadolibreAccount;

trait ResolvesMeliAccount
{
    protected function currentAccount(): ?MercadolibreAccount
    {
        $request    = request();
        $selectedId = $request->hasSession()
            ? $request->session()->get('active_meli_account_id')
            : $request->header('X-Meli-Account');

        return app(MercadolibreAccountRepositoryInterface::class)
            ->findSelectedByUser((int) auth()->id(), $selectedId ? (int) $selectedId : null);
    }
}
