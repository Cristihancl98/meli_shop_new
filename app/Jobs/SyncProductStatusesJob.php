<?php

namespace App\Jobs;

use App\Interfaces\ProductRepositoryInterface;
use App\Models\MercadolibreAccount;
use App\Models\Product;
use App\Services\MercadoLibreService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SyncProductStatusesJob implements ShouldQueue
{
    use Queueable;

    private const MELI_MULTIGET_LIMIT = 20;

    public int $tries   = 3;
    public int $timeout = 600;

    public function __construct(public readonly MercadolibreAccount $account) {}

    public function handle(MercadoLibreService $meliService, ProductRepositoryInterface $productRepository): void
    {
        $productRepository->meliItemIdsChunked($this->account->id, self::MELI_MULTIGET_LIMIT, function (array $itemIds) use ($meliService) {
            foreach ($meliService->getItems($this->account, $itemIds) as $entry) {
                $body = $entry['body'] ?? [];

                if (!isset($body['id'], $body['status'])) {
                    continue;
                }

                Product::where('meli_item_id', $body['id'])->update([
                    'status'        => Product::normalizeMeliStatus($body['status']),
                    'sold_quantity' => $body['sold_quantity'] ?? 0,
                    'last_sync'     => now(),
                ]);
            }
        });
    }
}
