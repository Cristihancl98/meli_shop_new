<?php

namespace App\Repositories;

use App\Interfaces\CustomerRepositoryInterface;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class CustomerRepository implements CustomerRepositoryInterface
{
    public function paginate(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        $query = Customer::withCount('orders')
            ->withSum('orders', 'total_amount')
            ->orderBy('created_at', 'desc');

        if (!empty($filters['account_id'])) {
            $query->where('mercadolibre_account_id', $filters['account_id']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('nickname', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%");
            });
        }

        return $query->paginate($perPage);
    }

    public function findById(int $id): ?Customer
    {
        return Customer::with([
            'orders' => fn ($q) => $q->orderBy('order_date', 'desc')->with('items'),
        ])->find($id);
    }

    public function findByMeliCustomerId(string $meliCustomerId): ?Customer
    {
        return Customer::where('meli_customer_id', $meliCustomerId)->first();
    }

    public function findByAccountId(int $accountId): Collection
    {
        return Customer::where('mercadolibre_account_id', $accountId)->get();
    }

    public function create(array $data): Customer
    {
        return Customer::create($data);
    }

    public function update(Customer $customer, array $data): Customer
    {
        $customer->update($data);
        return $customer->fresh();
    }

    public function topBuyers(int $accountId, int $limit = 10): Collection
    {
        return Customer::where('mercadolibre_account_id', $accountId)
            ->withCount('orders')
            ->withSum('orders', 'total_amount')
            ->orderByDesc('orders_sum_total_amount')
            ->limit($limit)
            ->get();
    }

    public function count(int $accountId): int
    {
        return Customer::where('mercadolibre_account_id', $accountId)->count();
    }
}
