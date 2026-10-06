<?php

namespace App\Jobs;

use App\Models\MercadolibreAccount;
use App\Services\PostSaleService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SyncPostSaleMessagesJob implements ShouldQueue
{
    use Queueable;

    public int $tries   = 3;
    public int $timeout = 900;

    public function __construct(
        public readonly MercadolibreAccount $account,
        public readonly bool $full = false,
    ) {}

    public function handle(PostSaleService $postSaleService): void
    {
        $this->full
            ? $postSaleService->downloadAll($this->account)
            : $postSaleService->syncUnread($this->account);
    }
}
