<?php

namespace App\Jobs;

use App\Models\MercadolibreAccount;
use App\Services\MercadoLibreService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RefreshMeliTokenJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public readonly MercadolibreAccount $account) {}

    public function handle(MercadoLibreService $meliService): void
    {
        $meliService->refreshAccessToken($this->account);
    }
}
