<?php

namespace App\Jobs;

use App\Enums\SettingKey;
use App\Models\MercadolibreAccount;
use App\Services\SettingService;
use App\Services\MercadoLibreService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SyncReputationJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public readonly MercadolibreAccount $account) {}

    public function handle(MercadoLibreService $meliService, SettingService $settingService): void
    {
        $levelId = $meliService->getSeller($this->account)['seller_reputation']['level_id'] ?? null;

        if ($levelId && str_contains($levelId, '_')) {
            $settingService->recordSystemValue(SettingKey::Reputation, explode('_', $levelId, 2)[1], $this->account);
        }
    }
}
