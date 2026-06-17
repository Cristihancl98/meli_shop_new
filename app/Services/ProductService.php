<?php

namespace App\Services;

use App\DTOs\ProductDTO;
use App\Events\ProductSynced;
use App\Interfaces\ProductRepositoryInterface;
use App\Models\MercadolibreAccount;
use App\Models\Product;
use App\Models\SyncLog;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;

class ProductService
{
    public function __construct(
        private readonly ProductRepositoryInterface $productRepository,
        private readonly MercadoLibreService $meliService
    ) {}

    public function list(MercadolibreAccount $account, array $filters = []): LengthAwarePaginator
    {
        return $this->productRepository->paginate($account->id, $filters);
    }

    public function find(int $id): ?Product
    {
        return $this->productRepository->find($id);
    }

    public function create(MercadolibreAccount $account, ProductDTO $dto, ?string $imagePath = null): Product
    {
        $log = SyncLog::create([
            'mercadolibre_account_id' => $account->id,
            'type'                    => 'products',
            'status'                  => 'running',
            'started_at'              => now(),
        ]);

        try {
            $thumbnailUrl = null;

            if ($imagePath) {
                $thumbnailUrl = $this->uploadImageToS3AndMeli($account, $imagePath);
            }

            $dtoWithImage = new ProductDTO(
                title:         $dto->title,
                price:         $dto->price,
                stock:         $dto->stock,
                status:        $dto->status,
                condition:     $dto->condition,
                description:   $dto->description,
                categoryId:    $dto->categoryId,
                listingTypeId: $dto->listingTypeId,
                thumbnail:     $thumbnailUrl,
            );

            $product = $this->productRepository->create(array_merge(
                $dtoWithImage->toArray(),
                ['mercadolibre_account_id' => $account->id]
            ));

            $meliCategoryId = $product->category?->meli_category_id ?? 'MCO1000';
            $meliData       = $this->meliService->createProduct($account, $dtoWithImage->toMeliPayload($meliCategoryId));

            if (isset($meliData['id'])) {
                $this->productRepository->update($product, [
                    'meli_item_id' => $meliData['id'],
                    'permalink'    => $meliData['permalink'] ?? null,
                    'last_sync'    => now(),
                ]);
            }

            $log->update(['status' => 'success', 'finished_at' => now()]);
            event(new ProductSynced($product->fresh()));

            return $product->fresh();
        } catch (\Throwable $e) {
            $log->update(['status' => 'error', 'message' => $e->getMessage(), 'finished_at' => now()]);
            throw $e;
        }
    }

    public function update(MercadolibreAccount $account, Product $product, array $data, ?string $imagePath = null): Product
    {
        $log = SyncLog::create([
            'mercadolibre_account_id' => $account->id,
            'type'                    => 'products',
            'status'                  => 'running',
            'started_at'              => now(),
        ]);

        try {
            if ($imagePath) {
                $data['thumbnail'] = $this->uploadImageToS3AndMeli($account, $imagePath);
            }

            $updated = $this->productRepository->update($product, $data);

            if ($product->meli_item_id) {
                $meliPayload = array_filter([
                    'price'              => $data['price'] ?? null,
                    'available_quantity' => $data['stock'] ?? null,
                    'title'              => $data['title'] ?? null,
                    'status'             => $data['status'] ?? null,
                ]);

                $this->meliService->updateProduct($account, $product->meli_item_id, $meliPayload);
            }

            $this->productRepository->update($updated, ['last_sync' => now()]);
            $log->update(['status' => 'success', 'finished_at' => now()]);
            event(new ProductSynced($updated->fresh()));

            return $updated->fresh();
        } catch (\Throwable $e) {
            $log->update(['status' => 'error', 'message' => $e->getMessage(), 'finished_at' => now()]);
            throw $e;
        }
    }

    public function delete(MercadolibreAccount $account, Product $product): bool
    {
        if ($product->meli_item_id) {
            $this->meliService->updateProduct($account, $product->meli_item_id, ['status' => 'closed']);
        }

        return $this->productRepository->delete($product);
    }

    public function syncFromMeli(MercadolibreAccount $account, Product $product): Product
    {
        $log = SyncLog::create([
            'mercadolibre_account_id' => $account->id,
            'type'                    => 'products',
            'status'                  => 'running',
            'started_at'              => now(),
        ]);

        try {
            $meliData = $this->meliService->getProduct($account, $product->meli_item_id);

            $updated = $this->productRepository->update($product, [
                'title'     => $meliData['title'] ?? $product->title,
                'price'     => $meliData['price'] ?? $product->price,
                'stock'     => $meliData['available_quantity'] ?? $product->stock,
                'status'    => $meliData['status'] ?? $product->status,
                'thumbnail' => $meliData['thumbnail'] ?? $product->thumbnail,
                'permalink' => $meliData['permalink'] ?? $product->permalink,
                'last_sync' => now(),
            ]);

            $log->update(['status' => 'success', 'finished_at' => now()]);
            event(new ProductSynced($updated));

            return $updated;
        } catch (\Throwable $e) {
            $log->update(['status' => 'error', 'message' => $e->getMessage(), 'finished_at' => now()]);
            throw $e;
        }
    }

    public function getStatusCounts(MercadolibreAccount $account): array
    {
        return $this->productRepository->countByStatus($account->id);
    }

    public function topSelling(MercadolibreAccount $account, int $limit = 10)
    {
        return $this->productRepository->topSelling($account->id, $limit);
    }

    private function uploadImageToS3AndMeli(MercadolibreAccount $account, string $localPath): string
    {
        $s3Path = 'products/' . basename($localPath);
        Storage::disk('s3')->put($s3Path, file_get_contents($localPath));
        $s3Url  = Storage::disk('s3')->url($s3Path);

        try {
            $this->meliService->uploadImage($account, $localPath);
        } catch (\Throwable) {
            // La imagen en S3 ya fue subida — la de MeLi es opcional
        }

        return $s3Url;
    }
}
