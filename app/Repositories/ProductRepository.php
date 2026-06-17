<?php

namespace App\Repositories;

use App\Interfaces\ProductRepositoryInterface;
use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class ProductRepository implements ProductRepositoryInterface
{
    public function paginate(int $accountId, array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = Product::with(['category', 'statistics'])
            ->where('mercadolibre_account_id', $accountId);

        if (!empty($filters['search'])) {
            $query->where('title', 'like', '%' . $filters['search'] . '%');
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (!empty($filters['min_price'])) {
            $query->where('price', '>=', $filters['min_price']);
        }

        if (!empty($filters['max_price'])) {
            $query->where('price', '<=', $filters['max_price']);
        }

        return $query->latest('updated_at')->paginate($perPage);
    }

    public function find(int $id): ?Product
    {
        return Product::with(['category', 'account', 'statistics'])->find($id);
    }

    public function findByMeliItemId(string $meliItemId): ?Product
    {
        return Product::where('meli_item_id', $meliItemId)->first();
    }

    public function create(array $data): Product
    {
        return Product::create($data);
    }

    public function update(Product $product, array $data): Product
    {
        $product->update($data);
        return $product->fresh(['category', 'statistics']);
    }

    public function delete(Product $product): bool
    {
        return $product->delete();
    }

    public function countByStatus(int $accountId): array
    {
        $counts = Product::where('mercadolibre_account_id', $accountId)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        return [
            'active' => $counts['active'] ?? 0,
            'paused' => $counts['paused'] ?? 0,
            'closed' => $counts['closed'] ?? 0,
        ];
    }

    public function topSelling(int $accountId, int $limit = 10): Collection
    {
        return Product::with('statistics')
            ->where('mercadolibre_account_id', $accountId)
            ->whereHas('statistics', fn ($q) => $q->where('quantity_sold', '>', 0))
            ->join('product_statistics', 'products.id', '=', 'product_statistics.product_id')
            ->orderByDesc('product_statistics.quantity_sold')
            ->select('products.*')
            ->limit($limit)
            ->get();
    }

    public function withoutStock(int $accountId): Collection
    {
        return Product::where('mercadolibre_account_id', $accountId)
            ->where('status', 'active')
            ->where('stock', 0)
            ->get();
    }
}
