<?php

namespace App\Jobs;

use App\Events\SyncFailed;
use App\Models\MercadolibreAccount;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductStatistic;
use App\Models\SalesStatistic;
use App\Models\SyncLog;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

class CalculateStatisticsJob implements ShouldQueue
{
    use Queueable;

    public int $tries   = 3;
    public int $timeout = 120;

    public function __construct(
        public readonly MercadolibreAccount $account
    ) {}

    public function handle(): void
    {
        $log = SyncLog::create([
            'mercadolibre_account_id' => $this->account->id,
            'type'                    => 'statistics',
            'status'                  => 'running',
            'started_at'              => now(),
        ]);

        try {
            $this->recalculateSalesStatistics();
            $this->recalculateProductStatistics();

            $log->update([
                'status'      => 'success',
                'finished_at' => now(),
                'message'     => 'Estadísticas recalculadas correctamente',
            ]);
        } catch (\Throwable $e) {
            $log->update(['status' => 'error', 'finished_at' => now(), 'message' => $e->getMessage()]);
            SyncFailed::dispatch($this->account, 'statistics', $e->getMessage());
            throw $e;
        }
    }

    private function recalculateSalesStatistics(): void
    {
        $dailyStats = Order::where('mercadolibre_account_id', $this->account->id)
            ->where('status', 'paid')
            ->whereNotNull('order_date')
            ->selectRaw('DATE(order_date) as stat_date, COUNT(*) as total_orders, SUM(total_amount) as total_sales')
            ->groupByRaw('DATE(order_date)')
            ->get();

        foreach ($dailyStats as $row) {
            SalesStatistic::updateOrCreate(
                [
                    'mercadolibre_account_id' => $this->account->id,
                    'stat_date'               => $row->stat_date,
                ],
                [
                    'total_orders'   => (int) $row->total_orders,
                    'total_sales'    => (float) $row->total_sales,
                    'average_ticket' => $row->total_orders > 0
                        ? round((float) $row->total_sales / (int) $row->total_orders, 2)
                        : 0,
                ]
            );
        }
    }

    private function recalculateProductStatistics(): void
    {
        $productStats = OrderItem::join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('orders.mercadolibre_account_id', $this->account->id)
            ->where('orders.status', 'paid')
            ->whereNotNull('order_items.product_id')
            ->selectRaw('
                order_items.product_id,
                SUM(order_items.quantity) AS quantity_sold,
                SUM(order_items.total_price) AS total_revenue,
                MAX(orders.order_date) AS last_sale_date
            ')
            ->groupBy('order_items.product_id')
            ->get();

        foreach ($productStats as $row) {
            ProductStatistic::updateOrCreate(
                ['product_id' => $row->product_id],
                [
                    'quantity_sold'  => (int) $row->quantity_sold,
                    'total_revenue'  => (float) $row->total_revenue,
                    'last_sale_date' => $row->last_sale_date,
                ]
            );
        }
    }
}
