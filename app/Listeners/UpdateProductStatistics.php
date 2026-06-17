<?php

namespace App\Listeners;

use App\Events\ProductSynced;
use App\Models\OrderItem;
use App\Models\ProductStatistic;
use Illuminate\Contracts\Queue\ShouldQueue;

class UpdateProductStatistics implements ShouldQueue
{
    public function handle(ProductSynced $event): void
    {
        $product = $event->product;

        $stats = OrderItem::where('product_id', $product->id)
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('orders.status', 'paid')
            ->selectRaw('
                SUM(order_items.quantity)    AS total_qty,
                SUM(order_items.total_price) AS total_rev,
                MAX(orders.order_date)       AS last_sale
            ')
            ->first();

        ProductStatistic::updateOrCreate(
            ['product_id' => $product->id],
            [
                'quantity_sold'  => (int) ($stats->total_qty ?? 0),
                'total_revenue'  => (float) ($stats->total_rev ?? 0),
                'last_sale_date' => $stats->last_sale ?? null,
            ]
        );
    }
}
