<?php

namespace App\Services;

use App\DTOs\PublishProductDTO;
use App\Enums\BatchItemStatus;
use App\Exceptions\BusinessRuleException;
use App\Exceptions\MeliApiException;
use App\Interfaces\ProductRepositoryInterface;
use App\Interfaces\PublishBatchRepositoryInterface;
use App\Interfaces\UserRepositoryInterface;
use App\Jobs\PublishBatchItemJob;
use App\Jobs\ScrapeBatchItemJob;
use App\Models\MercadolibreAccount;
use App\Models\Product;
use App\Models\PublishBatch;
use App\Models\PublishBatchItem;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class PublishBatchService
{
    public const MAX_SKUS = 100;

    private const MAX_PICTURES = 10;

    public function __construct(
        private readonly PublishBatchRepositoryInterface $batchRepository,
        private readonly ProductRepositoryInterface $productRepository,
        private readonly UserRepositoryInterface $userRepository,
        private readonly ProductPublishingService $publishingService
    ) {}

    public function list(MercadolibreAccount $account): LengthAwarePaginator
    {
        return $this->batchRepository->paginateForAccount($account->id);
    }

    public function find(MercadolibreAccount $account, int $batchId): ?PublishBatch
    {
        return $this->batchRepository->findForAccount($account->id, $batchId);
    }

    public function statusCounts(PublishBatch $batch): array
    {
        return $this->batchRepository->statusCounts($batch);
    }

    public function create(MercadolibreAccount $account, User $user, array $skus): PublishBatch
    {
        $items = collect($skus)
            ->map(fn ($sku) => trim((string) $sku))
            ->filter()
            ->unique(fn (string $sku) => mb_strtoupper($sku))
            ->map(fn (string $sku) => $this->productRepository->existsSku($account->id, $sku)
                ? ['sku' => $sku, 'status' => BatchItemStatus::Duplicate->value, 'error' => 'Este SKU ya está publicado en la cuenta.']
                : ['sku' => $sku, 'status' => BatchItemStatus::Queued->value])
            ->values()
            ->all();

        $batch = $this->batchRepository->create($account->id, $user->id, $items);

        $batch->items
            ->filter(fn (PublishBatchItem $item) => $item->status === BatchItemStatus::Queued)
            ->each(fn (PublishBatchItem $item) => ScrapeBatchItemJob::dispatch($item->id));

        return $batch;
    }

    public function scrapeItem(int $itemId): void
    {
        $item = $this->batchRepository->findItem($itemId);

        if (!$item || !in_array($item->status, [BatchItemStatus::Queued, BatchItemStatus::Scraping], true)) {
            return;
        }

        $account = $item->batch->account;
        $this->batchRepository->updateItem($item, ['status' => BatchItemStatus::Scraping]);

        try {
            $lookup   = $this->publishingService->lookupSku($account, $item->sku);
            $category = $lookup['categoria_meli'] ?? $this->suggestCategory($account, (string) $lookup['titulo']);

            $this->batchRepository->updateItem($item, [
                'status' => BatchItemStatus::Ready,
                'data'   => $lookup + ['suggested_category' => $category],
                'error'  => $category ? null : 'No se pudo sugerir una categoría: corrígelo antes de publicar.',
            ]);
        } catch (BusinessRuleException $e) {
            $this->batchRepository->updateItem($item, [
                'status' => $this->productRepository->existsSku($account->id, $item->sku) ? BatchItemStatus::Duplicate : BatchItemStatus::NotFound,
                'error'  => $e->getMessage(),
            ]);
        }
    }

    public function markScrapeFailed(int $itemId, string $reason): void
    {
        if ($item = $this->batchRepository->findItem($itemId)) {
            $this->batchRepository->updateItem($item, ['status' => BatchItemStatus::NotFound, 'error' => "La búsqueda falló: {$reason}"]);
        }
    }

    public function rescrape(PublishBatch $batch, array $itemIds): int
    {
        $items = $this->batchRepository->itemsByIds($batch, $itemIds)
            ->filter(fn (PublishBatchItem $item) => $item->status->canRescrape());

        foreach ($items as $item) {
            $this->batchRepository->updateItem($item, ['status' => BatchItemStatus::Queued, 'error' => null]);
            ScrapeBatchItemJob::dispatch($item->id);
        }

        return $items->count();
    }

    public function publish(PublishBatch $batch, User $user, array $itemIds): int
    {
        $items = $this->batchRepository->itemsByIds($batch, $itemIds)
            ->filter(fn (PublishBatchItem $item) => $item->status->canPublish());

        foreach ($items as $item) {
            $this->batchRepository->updateItem($item, ['status' => BatchItemStatus::Publishing, 'error' => null]);
            PublishBatchItemJob::dispatch($item->id, $user->id);
        }

        return $items->count();
    }

    public function publishItem(int $itemId, int $userId): void
    {
        $item = $this->batchRepository->findItem($itemId);

        if (!$item || $item->status !== BatchItemStatus::Publishing) {
            return;
        }

        $this->batchRepository->updateItem($item, ['attempts' => $item->attempts + 1]);

        try {
            $user = $this->userRepository->find($userId) ?? throw new BusinessRuleException('El usuario que inició la publicación ya no existe.');

            $product = $this->publishingService->publish($item->batch->account, $this->toPublishDto($item), $user);

            $this->markPublished($item, $product);
        } catch (\Throwable $e) {
            if (!$e instanceof BusinessRuleException) {
                report($e);
            }

            $this->markPublishFailed($item->id, $this->describe($e));
        }
    }

    public function markPublishFailed(int $itemId, string $reason): void
    {
        if ($item = $this->batchRepository->findItem($itemId)) {
            $this->batchRepository->updateItem($item, ['status' => BatchItemStatus::Failed, 'error' => $reason]);
        }
    }

    public function itemForCorrection(MercadolibreAccount $account, int $itemId): ?PublishBatchItem
    {
        $item = $this->batchRepository->findItemForAccount($account->id, $itemId);

        return $item && $item->data && $item->status !== BatchItemStatus::Published ? $item : null;
    }

    public function markPublishedFromEditor(MercadolibreAccount $account, int $itemId, Product $product): ?PublishBatchItem
    {
        $item = $this->batchRepository->findItemForAccount($account->id, $itemId);

        return $item ? $this->markPublished($item, $product) : null;
    }

    private function markPublished(PublishBatchItem $item, Product $product): PublishBatchItem
    {
        return $this->batchRepository->updateItem($item, [
            'status'       => BatchItemStatus::Published,
            'product_id'   => $product->id,
            'meli_item_id' => $product->meli_item_id,
            'error'        => null,
        ]);
    }

    private function suggestCategory(MercadolibreAccount $account, string $title): ?string
    {
        try {
            return $this->publishingService->suggestCategory($account, $title);
        } catch (\Throwable $e) {
            report($e);

            return null;
        }
    }

    private function toPublishDto(PublishBatchItem $item): PublishProductDTO
    {
        $data = $item->data ?? [];

        $fields = [
            'sku'              => $item->sku,
            'title'            => $item->title(),
            'meli_category_id' => $data['suggested_category'] ?? null,
            'final_price'      => $data['final_price'] ?? null,
            'base_price'       => $data['precio'] ?? 0,
            'quantity'         => max(1, (int) ($data['default_quantity'] ?? 1)),
            'weight'           => $data['peso'] ?? 0,
            'brand'            => $data['marca'] ?? null,
            'pictures'         => array_slice(array_values(array_filter((array) ($data['imagenes'] ?? []))), 0, self::MAX_PICTURES),
            'description'      => $data['descripcion'] ?? '',
            'height'           => $data['alto'] ?? null,
            'width'            => $data['ancho'] ?? null,
            'length'           => $data['largo'] ?? null,
            'model'            => $data['modelo'] ?? null,
            'ean'              => $data['ean'] ?? null,
            'extra_attributes' => collect($data['atributos_extra'] ?? [])
                ->filter(fn ($attribute) => !empty($attribute['id']) && ($attribute['value'] ?? '') !== '')
                ->map(fn ($attribute) => ['id' => $attribute['id'], 'value' => $attribute['value']])
                ->values()
                ->all(),
        ];

        $missing = array_keys(array_filter([
            'título'    => !$fields['title'],
            'categoría' => !$fields['meli_category_id'],
            'precio'    => !($fields['final_price'] > 0),
            'marca'     => !$fields['brand'],
            'fotos'     => $fields['pictures'] === [],
        ]));

        if ($missing !== []) {
            throw new BusinessRuleException('Faltan datos para publicar: ' . implode(', ', $missing) . '. Usa "Corregir" para completarlos.');
        }

        return PublishProductDTO::fromArray($fields);
    }

    private function describe(\Throwable $e): string
    {
        if (!$e instanceof MeliApiException) {
            return $e->getMessage();
        }

        $causes = array_filter(array_column($e->detailForDisplay()['causes'], 'message'));

        return $causes === [] ? $e->getMessage() : $e->getMessage() . ' — ' . implode(' · ', $causes);
    }
}
