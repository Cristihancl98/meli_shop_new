<?php

namespace App\Jobs;

use App\Events\ProductSynced;
use App\Events\SyncFailed;
use App\Exceptions\MeliApiException;
use App\Interfaces\ProductRepositoryInterface;
use App\Interfaces\SyncLogRepositoryInterface;
use App\Models\MercadolibreAccount;
use App\Models\SyncLog;
use App\Services\MeliItemMapper;
use App\Services\MercadoLibreService;
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

    public function handle(
        MercadoLibreService $meliService,
        ProductRepositoryInterface $repo,
        SyncLogRepositoryInterface $logs,
        MeliItemMapper $mapper
    ): void {
        $log    = $this->startLog($logs);
        $counts = ['created' => 0, 'updated' => 0, 'skipped' => 0];
        $seen   = [];

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
                        $result = $this->importEntry($repo, $mapper, $entry);
                        $counts[$result]++;

                        if (isset($entry['body']['id'])) {
                            $seen[MeliItemMapper::normalizeItemId($entry['body']['id'])] = true;
                        }
                    }
                }
            } while ($itemIds !== [] && $scrollId);

            $counts['updated'] += $this->refreshUnseen($meliService, $repo, $mapper, $seen);

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

    private function importEntry(ProductRepositoryInterface $repo, MeliItemMapper $mapper, array $entry): string
    {
        $body = $entry['body'] ?? [];

        if (($entry['code'] ?? 200) !== 200 || empty($body['id'])) {
            return 'skipped';
        }

        try {
            $existing = $repo->findByMeliItemIdIncludingArchived(MeliItemMapper::normalizeItemId($body['id']));

            $product = $existing
                ? $repo->update($existing, $mapper->toUpdateAttributes($body))
                : $repo->create($mapper->toProductAttributes($body) + ['mercadolibre_account_id' => $this->account->id]);

            if (!$product->trashed()) {
                ProductSynced::dispatch($product);
            }

            return $existing ? 'updated' : 'created';
        } catch (\Throwable $e) {
            Log::warning("SyncProductsJob: error en la publicación {$body['id']}", ['error' => $e->getMessage()]);

            return 'skipped';
        }
    }

    private function refreshUnseen(MercadoLibreService $meliService, ProductRepositoryInterface $repo, MeliItemMapper $mapper, array $seen): int
    {
        $unseen = [];
        $repo->meliItemIdsChunked($this->account->id, self::MULTIGET_LIMIT, function (array $itemIds) use ($seen, &$unseen) {
            foreach ($itemIds as $id) {
                if (!isset($seen[MeliItemMapper::normalizeItemId($id)])) {
                    $unseen[] = $id;
                }
            }
        });

        $refreshed = 0;

        foreach (array_chunk($unseen, self::MULTIGET_LIMIT) as $chunk) {
            foreach ($meliService->getItems($this->account, $chunk) as $i => $entry) {
                $itemId  = MeliItemMapper::normalizeItemId($entry['body']['id'] ?? $chunk[$i] ?? '');
                $product = $itemId !== '' ? $repo->findByMeliItemId($itemId) : null;

                if (!$product) {
                    continue;
                }

                $attributes = match ($entry['code'] ?? null) {
                    200     => $mapper->toUpdateAttributes($entry['body']),
                    404     => ['status' => 'closed', 'last_sync' => now()],
                    default => null,
                };

                if ($attributes) {
                    $repo->update($product, $attributes);
                    $refreshed++;
                }
            }
        }

        return $refreshed;
    }

    private function summary(array $counts): string
    {
        $summary = self::LOG_PREFIX . ": {$counts['created']} nuevas, {$counts['updated']} actualizadas";

        return $counts['skipped'] > 0 ? "{$summary}, {$counts['skipped']} omitidas" : $summary;
    }
}
