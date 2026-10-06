<?php

namespace App\Jobs;

use App\Enums\SettingKey;
use App\Events\SyncFailed;
use App\Models\MercadolibreAccount;
use App\Models\SyncLog;
use App\Services\SettingService;
use App\Services\MercadoLibreService;
use App\Services\OrderImportService;
use App\Services\PostSaleService;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SyncOrdersJob implements ShouldQueue
{
    use Queueable;

    public int $tries   = 3;
    public int $timeout = 300;

    public function __construct(
        public readonly MercadolibreAccount $account,
        public readonly ?string $dateFrom = null,
    ) {}

    public function handle(
        MercadoLibreService $meliService,
        OrderImportService $orderImportService,
        PostSaleService $postSaleService,
        SettingService $settingService
    ): void {
        $log = SyncLog::create([
            'mercadolibre_account_id' => $this->account->id,
            'type'                    => 'orders',
            'status'                  => 'running',
            'started_at'              => now(),
        ]);

        $processed = 0;
        $startedAt = now();

        try {
            $from   = $this->resolveDateFrom($settingService);
            $offset = 0;
            $limit  = 50;

            do {
                $response = $meliService->getOrders($this->account, [
                    'sort'                    => 'date_desc',
                    'order.date_created.from' => $from,
                    'offset'                  => $offset,
                    'limit'                   => $limit,
                ]);

                $meliOrders = $response['results'] ?? [];

                foreach ($meliOrders as $meliOrder) {
                    try {
                        $order = $orderImportService->import($this->account, $meliOrder);

                        if ($order->wasRecentlyCreated) {
                            $postSaleService->sendSaleMessage($this->account, $order);
                        }

                        $processed++;
                    } catch (\Throwable $e) {
                        Log::warning("SyncOrdersJob: error on order {$meliOrder['id']}", ['error' => $e->getMessage()]);
                    }
                }

                $offset += $limit;
                $total   = $response['paging']['total'] ?? 0;

            } while ($offset < $total && count($meliOrders) > 0);

            $settingService->recordSystemValue(SettingKey::OrdersSyncedAt, $startedAt->toDateTimeString(), $this->account);

            $log->update([
                'status'      => 'success',
                'finished_at' => now(),
                'message'     => "{$processed} órdenes sincronizadas",
            ]);
        } catch (\Throwable $e) {
            $log->update(['status' => 'error', 'finished_at' => now(), 'message' => $e->getMessage()]);
            SyncFailed::dispatch($this->account, 'orders', $e->getMessage());
            throw $e;
        }
    }

    private function resolveDateFrom(SettingService $settingService): string
    {
        if ($this->dateFrom) {
            return $this->dateFrom;
        }

        $lastSync = $settingService->get(SettingKey::OrdersSyncedAt, $this->account);

        return ($lastSync ? Carbon::parse($lastSync)->startOfDay() : Carbon::now()->subDay())->toIso8601String();
    }
}
