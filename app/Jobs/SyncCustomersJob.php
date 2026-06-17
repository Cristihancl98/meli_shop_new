<?php

namespace App\Jobs;

use App\Events\CustomerSynced;
use App\Events\SyncFailed;
use App\Models\Customer;
use App\Models\MercadolibreAccount;
use App\Models\SyncLog;
use App\Services\MercadoLibreService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SyncCustomersJob implements ShouldQueue
{
    use Queueable;

    public int $tries   = 3;
    public int $timeout = 300;

    public function __construct(
        public readonly MercadolibreAccount $account
    ) {}

    public function handle(MercadoLibreService $meliService): void
    {
        $log = SyncLog::create([
            'mercadolibre_account_id' => $this->account->id,
            'type'                    => 'customers',
            'status'                  => 'running',
            'started_at'              => now(),
        ]);

        $processed = 0;

        try {
            Customer::where('mercadolibre_account_id', $this->account->id)
                ->whereNotNull('meli_customer_id')
                ->chunkById(50, function ($customers) use ($meliService, &$processed) {
                    foreach ($customers as $customer) {
                        try {
                            $meliData = $meliService->getCustomer($this->account, $customer->meli_customer_id);

                            $name = trim(($meliData['first_name'] ?? '') . ' ' . ($meliData['last_name'] ?? ''));

                            $customer->update([
                                'name'     => $name ?: ($meliData['nickname'] ?? $customer->name),
                                'nickname' => $meliData['nickname'] ?? $customer->nickname,
                                'email'    => $meliData['email'] ?? $customer->email,
                                'phone'    => $meliData['phone']['number'] ?? $customer->phone,
                            ]);

                            CustomerSynced::dispatch($customer->fresh());
                            $processed++;
                        } catch (\Throwable $e) {
                            Log::warning("SyncCustomersJob: error on customer {$customer->meli_customer_id}", [
                                'error' => $e->getMessage(),
                            ]);
                        }
                    }
                });

            $log->update([
                'status'      => 'success',
                'finished_at' => now(),
                'message'     => "{$processed} clientes sincronizados",
            ]);
        } catch (\Throwable $e) {
            $log->update(['status' => 'error', 'finished_at' => now(), 'message' => $e->getMessage()]);
            SyncFailed::dispatch($this->account, 'customers', $e->getMessage());
            throw $e;
        }
    }
}
