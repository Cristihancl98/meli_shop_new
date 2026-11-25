<?php

namespace App\Jobs;

use App\Events\ProductSynced;
use App\Events\SyncFailed;
use App\Exceptions\MeliApiException;
use App\Interfaces\ProductRepositoryInterface;
use App\Interfaces\SyncLogRepositoryInterface;
use App\Models\Category;
use App\Models\MercadolibreAccount;
use App\Models\Product;
use App\Models\SyncLog;
use App\Services\MercadoLibreService;
use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Log;

class SyncProductsJob implements ShouldQueue
{
    use Queueable;

    public const LOG_PREFIX = 'Importación de publicaciones';

    private const MULTIGET_LIMIT = 20;

    public int $timeout       = 900;
    public int $maxExceptions = 3;

    public function __construct(
        public readonly MercadolibreAccount $account,
        public readonly ?int $syncLogId = null
    ) {}

    public function middleware(): array
    {
        return [(new WithoutOverlapping('sync-products-' . $this->account->id))->releaseAfter(60)->expireAfter($this->timeout)];
    }

    public function retryUntil(): DateTimeInterface
    {
        return now()->addHours(2);
    }

    public function handle(MercadoLibreService $meliService, ProductRepositoryInterface $repo, SyncLogRepositoryInterface $logs): void
    {
        $log    = $this->startLog($logs);
        $counts = ['created' => 0, 'updated' => 0, 'skipped' => 0];

        try {
            $scrollId = null;

            do {
                $page = $meliService->scanItems($this->account, $scrollId);

                if (!is_array($page['results'] ?? null)) {
                    throw MeliApiException::fromResponse('listar publicaciones', $page);
                }

                $itemIds  = $page['results'];
                $scrollId = $page['scroll_id'] ?? null;

                foreach (array_chunk($itemIds, self::MULTIGET_LIMIT) as $chunk) {
                    foreach ($meliService->getItems($this->account, $chunk) as $entry) {
                        $counts[$this->importEntry($repo, $entry)]++;
                    }
                }
            } while ($itemIds !== [] && $scrollId);

            $logs->update($log, [
                'status'      => 'success',
                'finished_at' => now(),
                'message'     => $this->summary($counts),
            ]);
        } catch (\Throwable $e) {
            $logs->update($log, ['status' => 'error', 'finished_at' => now(), 'message' => self::LOG_PREFIX . ': ' . $e->getMessage()]);
            SyncFailed::dispatch($this->account, 'products', $e->getMessage());
            throw $e;
        }
    }

    private function startLog(SyncLogRepositoryInterface $logs): SyncLog
    {
        $data = [
            'mercadolibre_account_id' => $this->account->id,
            'type'                    => 'products',
            'status'                  => 'running',
            'started_at'              => now(),
            'message'                 => self::LOG_PREFIX . ' en curso',
        ];

        $existing = $this->syncLogId ? $logs->find($this->syncLogId) : null;

        return $existing ? $logs->update($existing, $data) : $logs->create($data);
    }

    private function importEntry(ProductRepositoryInterface $repo, array $entry): string
    {
        $body = $entry['body'] ?? [];

        if (($entry['code'] ?? 200) !== 200 || empty($body['id'])) {
            return 'skipped';
        }

        try {
            $existing = $repo->findByMeliItemIdIncludingArchived($body['id']);

            if ($existing?->trashed()) {
                return 'skipped';
            }

            $data    = $this->mapMeliToLocal($body);
            $product = $existing
                ? $repo->update($existing, $data)
                : $repo->create($data + ['mercadolibre_account_id' => $this->account->id]);

            ProductSynced::dispatch($product);

            return $existing ? 'updated' : 'created';
        } catch (\Throwable $e) {
            Log::warning("SyncProductsJob: error en la publicación {$body['id']}", ['error' => $e->getMessage()]);

            return 'skipped';
        }
    }

    private function summary(array $counts): string
    {
        $summary = self::LOG_PREFIX . ": {$counts['created']} nuevas, {$counts['updated']} actualizadas";

        return $counts['skipped'] > 0 ? "{$summary}, {$counts['skipped']} omitidas" : $summary;
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
            'published_at'    => isset($data['date_created']) ? Carbon::parse($data['date_created']) : null,
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
            'thumbnail'       => $data['secure_thumbnail'] ?? $data['thumbnail'] ?? null,
            'permalink'       => $data['permalink'] ?? null,
            'last_sync'       => now(),
        ];
    }
}
