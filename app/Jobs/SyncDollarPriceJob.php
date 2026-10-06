<?php

namespace App\Jobs;

use App\Enums\SettingKey;
use App\Services\CatalogService;
use App\Services\SettingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SyncDollarPriceJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function handle(CatalogService $catalogService, SettingService $settingService): void
    {
        $dollar = $catalogService->currentDollarPrice();

        if ($dollar !== null) {
            $settingService->recordSystemValue(SettingKey::CurrentDollarPrice, $dollar);
        }
    }
}
