<?php

namespace App\Repositories;

use App\Interfaces\OrderRepositoryInterface;
use App\Models\Order;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class OrderRepository implements OrderRepositoryInterface
{
    public function paginate(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        $query = Order::with(['customer', 'items.product', 'account'])
            ->orderBy('order_date', 'desc');

        if (!empty($filters['account_id'])) {
            $query->where('mercadolibre_account_id', $filters['account_id']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['payment_status'])) {
            $query->where('payment_status', $filters['payment_status']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('order_date', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('order_date', '<=', $filters['date_to']);
        }

        if (!empty($filters['customer_name'])) {
            $query->whereHas('customer', function ($q) use ($filters) {
                $q->where('nickname', 'LIKE', "%{$filters['customer_name']}%")
                  ->orWhere('name', 'LIKE', "%{$filters['customer_name']}%");
            });
        }

        if (!empty($filters['min_amount'])) {
            $query->where('total_amount', '>=', $filters['min_amount']);
        }

        if (!empty($filters['max_amount'])) {
            $query->where('total_amount', '<=', $filters['max_amount']);
        }

        return $query->paginate($perPage);
    }

    public function findById(int $id): ?Order
    {
        return Order::with(['customer', 'items.product', 'account'])->find($id);
    }

    public function findByMeliOrderId(string $meliOrderId): ?Order
    {
        return Order::where('meli_order_id', $meliOrderId)->first();
    }

    public function findByAccountId(int $accountId): Collection
    {
        return Order::where('mercadolibre_account_id', $accountId)
            ->orderBy('order_date', 'desc')
            ->get();
    }

    public function create(array $data): Order
    {
        return Order::create($data);
    }

    public function update(Order $order, array $data): Order
    {
        $order->update($data);
        return $order->fresh();
    }

    public function countByStatus(int $accountId): array
    {
        $counts = Order::where('mercadolibre_account_id', $accountId)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        return [
            'paid'      => $counts['paid']      ?? 0,
            'pending'   => $counts['pending']   ?? 0,
            'cancelled' => $counts['cancelled'] ?? 0,
        ];
    }

    public function totalRevenue(int $accountId, ?string $dateFrom = null, ?string $dateTo = null): float
    {
        $query = Order::where('mercadolibre_account_id', $accountId)
            ->where('status', 'paid');

        if ($dateFrom) {
            $query->whereDate('order_date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->whereDate('order_date', '<=', $dateTo);
        }

        return (float) $query->sum('total_amount');
    }

    public function recentOrders(int $accountId, int $limit = 20): Collection
    {
        return Order::with(['customer', 'items'])
            ->where('mercadolibre_account_id', $accountId)
            ->orderBy('order_date', 'desc')
            ->limit($limit)
            ->get();
    }
}
