<?php

namespace App\Services;

use App\DTOs\PublishProductDTO;
use App\DTOs\UpdateListingDTO;
use App\Enums\SettingKey;
use App\Events\ProductSynced;
use App\Exceptions\BusinessRuleException;
use App\Exceptions\MeliApiException;
use App\Interfaces\ProductRepositoryInterface;
use App\Models\MercadolibreAccount;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

class ProductPublishingService
{
    public function __construct(
        private readonly ProductRepositoryInterface $productRepository,
        private readonly MercadoLibreService $meliService,
        private readonly CatalogService $catalogService,
        private readonly PricingService $pricingService,
        private readonly SettingService $settingService
    ) {}

    public function lookupSku(?MercadolibreAccount $account, string $sku): array
    {
        if ($account) {
            $this->ensureSkuIsFree($account, $sku);
        }

        $product = $this->catalogService->findProductBySku($sku);

        if (!$product) {
            throw new BusinessRuleException('No se encontró el producto en el catálogo.');
        }

        $breakdown = $this->pricingService->calculate($product['precio'], $product['peso']);

        return array_merge($product, [
            'final_price'      => $breakdown->finalPrice,
            'default_quantity' => (int) $this->settingService->get(SettingKey::DefaultQuantity),
            'price_breakdown'  => $breakdown->toArray(),
        ]);
    }

    public function suggestCategory(MercadolibreAccount $account, string $title): ?string
    {
        return $this->meliService->predictCategory($account, $title)[0]['category_id'] ?? null;
    }

    public function findLocalBySku(MercadolibreAccount $account, string $sku): Collection
    {
        return $this->productRepository->findBySku($account->id, $sku);
    }

    public function publish(MercadolibreAccount $account, PublishProductDTO $dto, User $publisher): Product
    {
        $this->ensureSkuIsFree($account, $dto->sku);

        $payload = $dto->toMeliPayload(
            config('services.mercadolibre.country_code'),
            (string) $this->settingService->get(SettingKey::ListingType),
            (int) $this->settingService->get(SettingKey::ManufacturingDays),
            (int) $this->settingService->get(SettingKey::WarrantyDays),
        );

        $meliItem = $this->meliService->createProduct($account, $payload);

        if (!isset($meliItem['id'])) {
            throw MeliApiException::fromResponse('publicar producto', $meliItem);
        }

        $descriptionSynced = $this->publishDescription($account, $meliItem['id'], $this->composeDescription($dto->description));

        $product = $this->productRepository->create([
            'mercadolibre_account_id' => $account->id,
            'meli_item_id'            => $meliItem['id'],
            'sku'                     => $dto->sku,
            'title'                   => $dto->title,
            'meli_category_id'        => $dto->meliCategoryId,
            'description'             => $dto->description,
            'description_synced'      => $descriptionSynced,
            'price'                   => $dto->finalPrice,
            'base_price'              => $dto->basePrice,
            'weight'                  => $dto->weight,
            'dimensions'              => $dto->dimensions(),
            'meli_attributes'         => $dto->meliAttributes(),
            'pictures'                => $dto->pictures,
            'stock'                   => $dto->quantity,
            'status'                  => 'active',
            'condition'               => 'new',
            'listing_type_id'         => $payload['listing_type_id'],
            'thumbnail'               => $dto->pictures[0] ?? null,
            'permalink'               => $meliItem['permalink'] ?? null,
            'published_by'            => $publisher->id,
            'published_at'            => now(),
            'last_sync'               => now(),
        ]);

        event(new ProductSynced($product));

        return $product;
    }

    public function changeStatus(MercadolibreAccount $account, Product $product, string $status): Product
    {
        $this->ensureMeliItem($product);

        $response = $this->meliService->changeItemStatus($account, $product->meli_item_id, $status);

        if (($response['status'] ?? null) !== $status) {
            throw MeliApiException::fromResponse('cambiar estado', $response);
        }

        return $this->productRepository->update($product, ['status' => $status, 'last_sync' => now()]);
    }

    public function updateListing(MercadolibreAccount $account, Product $product, UpdateListingDTO $dto): Product
    {
        $this->ensureMeliItem($product);

        if ($dto->isEmpty()) {
            throw new BusinessRuleException('No se enviaron datos para actualizar.');
        }

        $changes = [];

        if ($dto->description !== null) {
            $response = $this->meliService->updateItemDescription(
                $account,
                $product->meli_item_id,
                $this->composeDescription($dto->description)
            );

            if (!isset($response['last_updated'])) {
                throw MeliApiException::fromResponse('actualizar descripción', $response);
            }

            $changes['description']        = $dto->description;
            $changes['description_synced'] = true;
        }

        $itemPayload = $dto->meliItemPayload();

        if ($itemPayload !== []) {
            $response = $this->meliService->updateProduct($account, $product->meli_item_id, $itemPayload);

            if (!isset($response['last_updated'])) {
                throw MeliApiException::fromResponse('actualizar publicación', $response);
            }

            $changes += array_filter([
                'title'      => $dto->title,
                'price'      => $dto->price,
                'base_price' => $dto->basePrice,
                'stock'      => $dto->quantity,
                'pictures'   => $dto->pictures,
                'thumbnail'  => $dto->pictures[0] ?? null,
            ], fn ($value) => $value !== null);
        }

        $changes['last_sync'] = now();

        return $this->productRepository->update($product, $changes);
    }

    public function archive(Product $product): bool
    {
        return $this->productRepository->delete($product);
    }

    public function summary(MercadolibreAccount $account): array
    {
        return array_merge(
            $this->productRepository->countByStatus($account->id),
            $this->settingService->dollarComparison()
        );
    }

    private function composeDescription(string $description): string
    {
        $template = (string) $this->settingService->get(SettingKey::DescriptionTemplate);

        return trim($description . ($template !== '' ? "\n\n" . $template : ''));
    }

    private function publishDescription(MercadolibreAccount $account, string $itemId, string $description): bool
    {
        if ($description === '') {
            return false;
        }

        $response = $this->meliService->createItemDescription($account, $itemId, $description);

        if (isset($response['text']) || isset($response['plain_text'])) {
            return true;
        }

        Log::warning('No se pudo publicar la descripción en MeLi', ['item' => $itemId, 'response' => $response]);

        return false;
    }

    private function ensureSkuIsFree(MercadolibreAccount $account, string $sku): void
    {
        if ($this->productRepository->existsSku($account->id, $sku)) {
            throw new BusinessRuleException('El producto ya se encuentra registrado.');
        }
    }

    private function ensureMeliItem(Product $product): void
    {
        if (!$product->meli_item_id) {
            throw new BusinessRuleException('El producto no está publicado en Mercado Libre.');
        }
    }
}
