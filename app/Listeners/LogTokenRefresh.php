<?php

namespace App\Listeners;

use App\Events\MeliTokenRefreshed;
use Illuminate\Support\Facades\Log;

class LogTokenRefresh
{
    public function handle(MeliTokenRefreshed $event): void
    {
        Log::info('MeliTokenRefreshed', [
            'account_id'    => $event->account->id,
            'meli_user_id'  => $event->account->meli_user_id,
            'expires_at'    => $event->account->expires_at,
        ]);
    }
}
