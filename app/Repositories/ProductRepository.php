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

        if (!empty($filters['sku'])) {
            $query->where('sku', $filters['sku']);
        }

        if (!empty($filters['published_on'])) {
            $query->where(fn ($q) => $q
                ->whereDate('published_at', $filters['published_on'])
                ->orWhere(fn ($q) => $q->whereNull('published_at')->whereDate('created_at', $filters['published_on'])));
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

        return $query->latest('updated_at')->paginate($perPage)->withQueryString();
    }

    public function find(int $id): ?Product
    {
        return Product::with(['category', 'account', 'statistics'])->find($id);
    }

    public function findByMeliItemId(string $meliItemId): ?Product
    {
        return Product::where('meli_item_id', $meliItemId)->first();
    }

    public function findBySku(int $accountId, string $sku): Collection
    {
        return Product::where('mercadolibre_account_id', $accountId)->where('sku', $sku)->get();
    }

    public function existsSku(int $accountId, string $sku): bool
    {
        return Product::withTrashed()
            ->where('mercadolibre_account_id', $accountId)
            ->where('sku', $sku)
            ->exists();
    }

    public function findForAccount(int $accountId, int $id): ?Product
    {
        return Product::with(['category', 'statistics'])
            ->where('mercadolibre_account_id', $accountId)
            ->find($id);
    }

    public function meliItemIdsChunked(int $accountId, int $size, callable $callback): void
    {
        Product::where('mercadolibre_account_id', $accountId)
            ->whereNotNull('meli_item_id')
            ->orderBy('last_sync')
            ->select(['id', 'meli_item_id'])
            ->chunkById($size, fn ($products) => $callback($products->pluck('meli_item_id')->all()));
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
            'active'       => $counts['active'] ?? 0,
            'paused'       => $counts['paused'] ?? 0,
            'closed'       => $counts['closed'] ?? 0,
            'under_review' => $counts['under_review'] ?? 0,
            'inactive'     => $counts['inactive'] ?? 0,
            'total'        => array_sum($counts),
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
