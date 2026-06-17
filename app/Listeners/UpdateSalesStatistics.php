<?php

namespace App\Listeners;

use App\Events\OrderSynced;
use App\Models\OrderItem;
use App\Models\ProductStatistic;
use App\Models\SalesStatistic;
use Illuminate\Contracts\Queue\ShouldQueue;

class UpdateSalesStatistics implements ShouldQueue
{
    public function handle(OrderSynced $event): void
    {
        $order = $event->order;

        if ($order->status !== 'paid') {
            return;
        }

        $date      = $order->order_date?->toDateString() ?? now()->toDateString();
        $accountId = $order->mercadolibre_account_id;

        $dayStats = \App\Models\Order::where('mercadolibre_account_id', $accountId)
            ->where('status', 'paid')
            ->whereDate('order_date', $date)
            ->selectRaw('COUNT(*) as total_orders, SUM(total_amount) as total_sales')
            ->first();

        $totalOrders = (int) ($dayStats->total_orders ?? 0);
        $totalSales  = (float) ($dayStats->total_sales ?? 0);

        SalesStatistic::updateOrCreate(
            ['mercadolibre_account_id' => $accountId, 'stat_date' => $date],
            [
                'total_orders'   => $totalOrders,
                'total_sales'    => $totalSales,
                'average_ticket' => $totalOrders > 0 ? round($totalSales / $totalOrders, 2) : 0,
            ]
        );

        // Actualizar product_statistics para cada item de esta orden
        foreach ($order->items as $item) {
            if (!$item->product_id) {
                continue;
            }

            $productStats = OrderItem::where('product_id', $item->product_id)
                ->join('orders', 'order_items.order_id', '=', 'orders.id')
                ->where('orders.status', 'paid')
                ->selectRaw('SUM(order_items.quantity) AS qty, SUM(order_items.total_price) AS rev, MAX(orders.order_date) AS last_sale')
                ->first();

            ProductStatistic::updateOrCreate(
                ['product_id' => $item->product_id],
                [
                    'quantity_sold'  => (int) ($productStats->qty ?? 0),
                    'total_revenue'  => (float) ($productStats->rev ?? 0),
                    'last_sale_date' => $productStats->last_sale ?? null,
                ]
            );
        }
    }
}
