<?php

namespace App\Interfaces;

use App\Models\Order;
use Illuminate\Pagination\LengthAwarePaginator;

interface OrderRepositoryInterface
{
    public function paginate(array $filters, int $perPage = 20): LengthAwarePaginator;

    public function findById(int $id): ?Order;

    public function findByMeliOrderId(string $meliOrderId): ?Order;

    public function findByAccountId(int $accountId): \Illuminate\Database\Eloquent\Collection;

    public function findForAccount(int $accountId, int $id): ?Order;

    public function findByConversation(int $accountId, string $packOrOrderId): ?Order;

    public function create(array $data): Order;

    public function update(Order $order, array $data): Order;

    public function countByStatus(int $accountId): array;

    public function totalRevenue(int $accountId, ?string $dateFrom = null, ?string $dateTo = null): float;

    public function recentOrders(int $accountId, int $limit = 20): \Illuminate\Database\Eloquent\Collection;
}
