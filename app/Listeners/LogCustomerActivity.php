<?php

namespace App\Listeners;

use App\Events\CustomerSynced;
use Illuminate\Support\Facades\Log;

class LogCustomerActivity
{
    public function handle(CustomerSynced $event): void
    {
        Log::channel('stack')->info('CustomerSynced', [
            'customer_id'      => $event->customer->id,
            'meli_customer_id' => $event->customer->meli_customer_id,
            'name'             => $event->customer->name,
        ]);
    }
}
