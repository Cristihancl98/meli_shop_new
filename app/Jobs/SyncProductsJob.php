<?php

namespace App\Jobs;

use App\Events\ProductSynced;
use App\Events\SyncFailed;
use App\Interfaces\ProductRepositoryInterface;
use App\Models\Category;
use App\Models\MercadolibreAccount;
use App\Models\Product;
use App\Models\SyncLog;
use App\Services\MercadoLibreService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SyncProductsJob implements ShouldQueue
{
    use Queueable;

    public int $tries   = 3;
    public int $timeout = 300;

    public function __construct(public readonly MercadolibreAccount $account) {}

    public function handle(MercadoLibreService $meliService, ProductRepositoryInterface $repo): void
    {
        $log = SyncLog::create([
            'mercadolibre_account_id' => $this->account->id,
            'type'                    => 'products',
            'status'                  => 'running',
            'started_at'              => now(),
        ]);

        $processed = 0;

        try {
            $offset  = 0;
            $limit   = 50;

            do {
                $response = $meliService->getProducts($this->account, $offset, $limit);
                $itemIds  = $response['results'] ?? [];

                foreach ($itemIds as $itemId) {
                    try {
                        $meliData = $meliService->getProduct($this->account, $itemId);
                        $data     = $this->mapMeliToLocal($meliData);
                        $existing = $repo->findByMeliItemId($itemId);

                        if ($existing) {
                            $product = $repo->update($existing, $data);
                        } else {
                            $product = $repo->create(array_merge($data, [
                                'mercadolibre_account_id' => $this->account->id,
                            ]));
                        }

                        ProductSynced::dispatch($product);
                        $processed++;
                    } catch (\Throwable $e) {
                        Log::warning("SyncProductsJob: error on item {$itemId}", ['error' => $e->getMessage()]);
                    }
                }

                $offset += $limit;
                $total   = $response['paging']['total'] ?? 0;

            } while ($offset < $total && count($itemIds) > 0);

            $log->update([
                'status'      => 'success',
                'finished_at' => now(),
                'message'     => "{$processed} productos sincronizados",
            ]);
        } catch (\Throwable $e) {
            $log->update(['status' => 'error', 'finished_at' => now(), 'message' => $e->getMessage()]);
            SyncFailed::dispatch($this->account, 'products', $e->getMessage());
            throw $e;
        }
    }

    private function mapMeliToLocal(array $data): array
    {
        $categoryId = null;
        if (!empty($data['category_id'])) {
            $cat = Category::where('meli_category_id', $data['category_id'])->first();
            $categoryId = $cat?->id;
        }

        $sku = collect($data['attributes'] ?? [])->firstWhere('id', 'SELLER_SKU')['value_name'] ?? null;

        return [
            'meli_item_id'    => $data['id'],
            'meli_category_id'=> $data['category_id'] ?? null,
            'published_at'    => isset($data['date_created']) ? \Carbon\Carbon::parse($data['date_created']) : null,
            'sku'             => $sku,
            'meli_attributes' => $data['attributes'] ?? null,
            'pictures'        => array_values(array_filter(array_column($data['pictures'] ?? [], 'secure_url'))),
            'variations'      => $data['variations'] ?? null,
            'sold_quantity'   => $data['sold_quantity'] ?? 0,
            'category_id'     => $categoryId,
            'title'           => $data['title'] ?? '',
            'price'           => $data['price'] ?? 0,
            'stock'           => $data['available_quantity'] ?? 0,
            'status'          => Product::normalizeMeliStatus($data['status'] ?? null),
            'condition'       => $data['condition'] ?? 'new',
            'listing_type_id' => $data['listing_type_id'] ?? null,
            'thumbnail'       => $data['thumbnail'] ?? null,
            'permalink'       => $data['permalink'] ?? null,
            'last_sync'       => now(),
        ];
    }
}
