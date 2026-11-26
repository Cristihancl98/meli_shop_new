<?php

namespace App\Jobs;

use App\Events\SyncFailed;
use App\Interfaces\SyncLogRepositoryInterface;
use App\Models\MercadolibreAccount;
use App\Services\AmazonPriceSyncService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;

class SyncAmazonPricesJob implements ShouldQueue
{
    use Queueable;

    public const LOG_PREFIX = 'Precios Amazon';

    public int $tries   = 1;
    public int $timeout = 1200;

    public function __construct(public readonly MercadolibreAccount $account) {}

    public function middleware(): array
    {
        return [(new WithoutOverlapping('amazon-prices-' . $this->account->id))->dontRelease()->expireAfter($this->timeout)];
    }

    public function handle(AmazonPriceSyncService $service, SyncLogRepositoryInterface $logs): void
    {
        $log = $logs->create([
            'mercadolibre_account_id' => $this->account->id,
            'type'                    => 'products',
            'status'                  => 'running',
            'started_at'              => now(),
            'message'                 => self::LOG_PREFIX . ' en curso',
        ]);

        try {
            $summary = $service->syncAccount($this->account);

            $logs->update($log, ['status' => 'success', 'finished_at' => now(), 'message' => self::LOG_PREFIX . ': ' . $summary->message()]);
        } catch (\Throwable $e) {
            $logs->update($log, ['status' => 'error', 'finished_at' => now(), 'message' => self::LOG_PREFIX . ': ' . $e->getMessage()]);
            SyncFailed::dispatch($this->account, 'products', $e->getMessage());
            throw $e;
        }
    }
}
