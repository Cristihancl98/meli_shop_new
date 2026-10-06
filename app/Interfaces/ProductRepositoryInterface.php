<?php

namespace App\Interfaces;

use App\Models\Product;
use Illuminate\Pagination\LengthAwarePaginator;

interface ProductRepositoryInterface
{
    public function paginate(int $accountId, array $filters = [], int $perPage = 20): LengthAwarePaginator;
    public function find(int $id): ?Product;
    public function findByMeliItemId(string $meliItemId): ?Product;
    public function findBySku(int $accountId, string $sku): \Illuminate\Database\Eloquent\Collection;
    public function existsSku(int $accountId, string $sku): bool;
    public function findForAccount(int $accountId, int $id): ?Product;
    public function meliItemIdsChunked(int $accountId, int $size, callable $callback): void;
    public function create(array $data): Product;
    public function update(Product $product, array $data): Product;
    public function delete(Product $product): bool;
    public function countByStatus(int $accountId): array;
    public function topSelling(int $accountId, int $limit = 10): \Illuminate\Database\Eloquent\Collection;
    public function withoutStock(int $accountId): \Illuminate\Database\Eloquent\Collection;
}
