<?php

namespace App\Interfaces;

use App\Models\Customer;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface CustomerRepositoryInterface
{
    public function paginate(array $filters, int $perPage = 20): LengthAwarePaginator;

    public function findById(int $id): ?Customer;

    public function findByMeliCustomerId(string $meliCustomerId): ?Customer;

    public function findByAccountId(int $accountId): Collection;

    public function create(array $data): Customer;

    public function update(Customer $customer, array $data): Customer;

    public function topBuyers(int $accountId, int $limit = 10): Collection;

    public function count(int $accountId): int;
}
