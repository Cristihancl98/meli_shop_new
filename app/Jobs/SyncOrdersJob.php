<?php

namespace App\Jobs;

use App\Events\OrderSynced;
use App\Events\SyncFailed;
use App\Models\Customer;
use App\Models\MercadolibreAccount;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\SyncLog;
use App\Services\MercadoLibreService;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
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

    public function handle(MercadoLibreService $meliService): void
    {
        $log = SyncLog::create([
            'mercadolibre_account_id' => $this->account->id,
            'type'                    => 'orders',
            'status'                  => 'running',
            'started_at'              => now(),
        ]);

        $processed = 0;

        try {
            $from   = $this->dateFrom ?? Carbon::now()->subHours(24)->toIso8601String();
            $offset = 0;
            $limit  = 50;

            do {
                $response = $meliService->getOrders($this->account, [
                    'sort'               => 'date_desc',
                    'date_created.from'  => $from,
                    'offset'             => $offset,
                    'limit'              => $limit,
                ]);

                $meliOrders = $response['results'] ?? [];

                foreach ($meliOrders as $meliOrder) {
                    try {
                        DB::transaction(function () use ($meliOrder, &$processed) {
                            $customerId = $this->ensureCustomer($meliOrder);
                            $orderData  = $this->mapOrderToLocal($meliOrder, $customerId);

                            $order = Order::updateOrCreate(
                                ['meli_order_id' => $orderData['meli_order_id']],
                                $orderData
                            );

                            if ($order->wasRecentlyCreated) {
                                $this->syncOrderItems($order, $meliOrder['order_items'] ?? []);
                            }

                            OrderSynced::dispatch($order->fresh(['items']));
                            $processed++;
                        });
                    } catch (\Throwable $e) {
                        Log::warning("SyncOrdersJob: error on order {$meliOrder['id']}", ['error' => $e->getMessage()]);
                    }
                }

                $offset += $limit;
                $total   = $response['paging']['total'] ?? 0;

            } while ($offset < $total && count($meliOrders) > 0);

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

    private function ensureCustomer(array $meliOrder): ?int
    {
        $buyer = $meliOrder['buyer'] ?? null;
        if (!$buyer) {
            return null;
        }

        $name = trim(($buyer['first_name'] ?? '') . ' ' . ($buyer['last_name'] ?? ''));

        $customer = Customer::firstOrCreate(
            ['meli_customer_id' => (string) $buyer['id']],
            [
                'mercadolibre_account_id' => $this->account->id,
                'name'                    => $name ?: ($buyer['nickname'] ?? 'Sin nombre'),
                'nickname'                => $buyer['nickname'] ?? null,
                'email'                   => $buyer['email'] ?? null,
            ]
        );

        return $customer->id;
    }

    private function mapOrderToLocal(array $data, ?int $customerId): array
    {
        return [
            'meli_order_id'           => (string) $data['id'],
            'mercadolibre_account_id' => $this->account->id,
            'customer_id'             => $customerId,
            'status'                  => $data['status'] ?? 'pending',
            'payment_status'          => $data['payments'][0]['status'] ?? 'pending',
            'shipping_status'         => $data['shipping']['status'] ?? null,
            'total_amount'            => (float) ($data['total_amount'] ?? 0),
            'order_date'              => $data['date_created'] ? Carbon::parse($data['date_created']) : now(),
        ];
    }

    private function syncOrderItems(Order $order, array $items): void
    {
        foreach ($items as $item) {
            $meliItemId = $item['item']['id'] ?? null;
            $product    = $meliItemId ? Product::where('meli_item_id', $meliItemId)->first() : null;

            OrderItem::create([
                'order_id'     => $order->id,
                'product_id'   => $product?->id,
                'meli_item_id' => $meliItemId,
                'title'        => $item['item']['title'] ?? '',
                'quantity'     => (int) ($item['quantity'] ?? 1),
                'unit_price'   => (float) ($item['unit_price'] ?? 0),
                'total_price'  => (float) (($item['quantity'] ?? 1) * ($item['unit_price'] ?? 0)),
            ]);
        }
    }
}
