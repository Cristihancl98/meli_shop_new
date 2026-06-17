<?php

namespace App\Events;

use App\Models\MercadolibreAccount;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SyncFailed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly ?MercadolibreAccount $account,
        public readonly string $type,
        public readonly string $message
    ) {}
}
