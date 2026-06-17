<?php

namespace App\Listeners;

use App\Events\SyncFailed;
use Illuminate\Support\Facades\Log;

class NotifyAdminOfFailure
{
    public function handle(SyncFailed $event): void
    {
        Log::error('SyncFailed', [
            'account_id' => $event->account?->id,
            'type'       => $event->type,
            'message'    => $event->message,
        ]);
    }
}
