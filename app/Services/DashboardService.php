<?php

namespace App\Services;

use App\Interfaces\CustomerRepositoryInterface;
use App\Interfaces\MercadolibreAccountRepositoryInterface;
use App\Interfaces\OrderRepositoryInterface;
use App\Interfaces\ProductRepositoryInterface;
use App\Models\Order;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class DashboardService
{
    public function __construct(
        private readonly ProductRepositoryInterface              $productRepository,
        private readonly OrderRepositoryInterface               $orderRepository,
        private readonly CustomerRepositoryInterface            $customerRepository,
        private readonly MercadolibreAccountRepositoryInterface $accountRepository,
    ) {}

    public function getMetrics(int $userId, ?int $selectedAccountId = null): array
    {
        $account = $this->accountRepository->findSelectedByUser($userId, $selectedAccountId ?? null);
        if (!$account) {
            return $this->emptyMetrics();
        }

        $accountId  = $account->id;
        $now        = Carbon::now();
        $monthStart = $now->copy()->startOfMonth()->toDateString();
        $monthEnd   = $now->copy()->endOfMonth()->toDateString();

        $statusCounts  = $this->productRepository->countByStatus($accountId);
        $orderStatus   = $this->orderRepository->countByStatus($accountId);
        $totalCustomers = $this->customerRepository->count($accountId);
        $monthRevenue  = $this->orderRepository->totalRevenue($accountId, $monthStart, $monthEnd);
        $monthOrders   = Order::where('mercadolibre_account_id', $accountId)
            ->where('status', 'paid')
            ->whereDate('order_date', '>=', $monthStart)
            ->count();

        $lowStockCount = Product::where('mercadolibre_account_id', $accountId)
            ->where('status', 'active')
            ->where('stock', '<=', 5)
            ->count();

        return [
            'products_active'   => $statusCounts['active'],
            'products_paused'   => $statusCounts['paused'],
            'products_closed'   => $statusCounts['closed'],
            'orders_paid'       => $orderStatus['paid'],
            'orders_pending'    => $orderStatus['pending'],
            'orders_cancelled'  => $orderStatus['cancelled'],
            'total_customers'   => $totalCustomers,
            'month_revenue'     => $monthRevenue,
            'month_orders'      => $monthOrders,
            'avg_ticket'        => $monthOrders > 0 ? round($monthRevenue / $monthOrders) : 0,
            'low_stock_count'   => $lowStockCount,
        ];
    }

    public function getTopProducts(int $userId, int $limit = 10, ?int $selectedAccountId = null): Collection
    {
        $account = $this->accountRepository->findSelectedByUser($userId, $selectedAccountId ?? null);
        if (!$account) {
            return collect();
        }

        return $this->productRepository->topSelling($account->id, $limit);
    }

    public function getRecentOrders(int $userId, int $limit = 20, ?int $selectedAccountId = null): Collection
    {
        $account = $this->accountRepository->findSelectedByUser($userId, $selectedAccountId ?? null);
        if (!$account) {
            return collect();
        }

        return $this->orderRepository->recentOrders($account->id, $limit);
    }

    public function getAlerts(int $userId, ?int $selectedAccountId = null): array
    {
        $account = $this->accountRepository->findSelectedByUser($userId, $selectedAccountId ?? null);
        if (!$account) {
            return [];
        }

        $alerts = [];

        $lowStock = Product::where('mercadolibre_account_id', $account->id)
            ->where('status', 'active')
            ->where('stock', '<=', 5)
            ->orderBy('stock')
            ->limit(5)
            ->get(['id', 'title', 'stock']);

        foreach ($lowStock as $product) {
            $alerts[] = [
                'type'    => 'warning',
                'icon'    => 'bi-exclamation-triangle',
                'message' => "Stock bajo: \"{$product->title}\" — {$product->stock} unidades",
                'link'    => "/products/{$product->id}",
            ];
        }

        $pendingOrders = Order::where('mercadolibre_account_id', $account->id)
            ->where('status', 'pending')
            ->count();

        if ($pendingOrders > 0) {
            $alerts[] = [
                'type'    => 'info',
                'icon'    => 'bi-clock-history',
                'message' => "{$pendingOrders} " . ($pendingOrders === 1 ? 'orden pendiente' : 'órdenes pendientes') . " de pago",
                'link'    => '/orders?status=pending',
            ];
        }

        return $alerts;
    }

    public function getDailySales(int $userId, int $days = 30, ?int $selectedAccountId = null): array
    {
        $account = $this->accountRepository->findSelectedByUser($userId, $selectedAccountId ?? null);
        if (!$account) {
            return ['labels' => [], 'revenue' => [], 'count' => []];
        }

        $from = Carbon::now()->subDays($days - 1)->startOfDay();

        $rows = Order::where('mercadolibre_account_id', $account->id)
            ->where('status', 'paid')
            ->where('order_date', '>=', $from)
            ->selectRaw('DATE(order_date) as date, SUM(total_amount) as revenue, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        $labels  = [];
        $revenue = [];
        $count   = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date    = Carbon::now()->subDays($i)->format('Y-m-d');
            $label   = Carbon::now()->subDays($i)->locale('es')->isoFormat('D MMM');
            $row     = $rows->get($date);
            $labels[]  = $label;
            $revenue[] = $row ? (float) $row->revenue : 0;
            $count[]   = $row ? (int) $row->count : 0;
        }

        return compact('labels', 'revenue', 'count');
    }

    public function getMonthlyRevenue(int $userId, int $months = 6, ?int $selectedAccountId = null): array
    {
        $account = $this->accountRepository->findSelectedByUser($userId, $selectedAccountId ?? null);
        if (!$account) {
            return ['labels' => [], 'revenue' => []];
        }

        $from = Carbon::now()->subMonths($months - 1)->startOfMonth();

        $rows = Order::where('mercadolibre_account_id', $account->id)
            ->where('status', 'paid')
            ->where('order_date', '>=', $from)
            ->selectRaw("DATE_FORMAT(order_date, '%Y-%m') as month, SUM(total_amount) as revenue")
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->keyBy('month');

        $labels  = [];
        $revenue = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $date    = Carbon::now()->subMonths($i);
            $key     = $date->format('Y-m');
            $label   = $date->locale('es')->isoFormat('MMM YYYY');
            $row     = $rows->get($key);
            $labels[]  = $label;
            $revenue[] = $row ? (float) $row->revenue : 0;
        }

        return compact('labels', 'revenue');
    }

    private function emptyMetrics(): array
    {
        return [
            'products_active'  => 0, 'products_paused'  => 0, 'products_closed'  => 0,
            'orders_paid'      => 0, 'orders_pending'   => 0, 'orders_cancelled' => 0,
            'total_customers'  => 0, 'month_revenue'    => 0, 'month_orders'     => 0,
            'avg_ticket'       => 0, 'low_stock_count'  => 0,
        ];
    }
}
