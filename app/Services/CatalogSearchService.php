<?php

namespace App\Services;

use App\DTOs\CatalogSearchDTO;
use App\DTOs\CatalogSearchResultDTO;
use App\Interfaces\ExternalCatalogRepositoryInterface;
use App\Interfaces\ProductRepositoryInterface;
use App\Models\MercadolibreAccount;

class CatalogSearchService
{
    public const MELI_TITLE_LIMIT = 60;
    public const MAX_RESULTS      = 200;
    public const MAX_SCANNED      = 5000;

    public function __construct(
        private readonly ExternalCatalogRepositoryInterface $catalogRepository,
        private readonly ProductRepositoryInterface $productRepository,
        private readonly PricingService $pricingService
    ) {}

    public function search(CatalogSearchDTO $criteria, ?MercadolibreAccount $account): CatalogSearchResultDTO
    {
        $items   = [];
        $scanned = 0;

        foreach ($this->catalogRepository->iterate($criteria->mode, $criteria->term) as $document) {
            if ($scanned >= self::MAX_SCANNED) {
                break;
            }

            $scanned++;
            $item = $this->evaluate($document);

            if ($this->matches($item, $criteria)) {
                $items[] = $item;
            }

            if (count($items) >= self::MAX_RESULTS) {
                break;
            }
        }

        $items = $this->withPricesAndPublishedFlags($items, $account);

        return new CatalogSearchResultDTO($items, $scanned, count($items) >= self::MAX_RESULTS || $scanned >= self::MAX_SCANNED);
    }

    private function evaluate(array $document): array
    {
        $price  = is_numeric($document['precio'] ?? null) ? (float) $document['precio'] : null;
        $weight = is_numeric($document['peso'] ?? null) ? (float) $document['peso'] : null;
        $title  = trim((string) (($document['tituloMeli'] ?? '') ?: ($document['titulo'] ?? '')));
        $length = mb_strlen($title);
        $images = array_values(array_filter((array) ($document['imagenes'] ?? []), fn ($url) => is_string($url) && trim($url) !== ''));

        $missing = [];
        if (!($price > 0)) {
            $missing[] = 'precio';
        }
        if (!($weight > 0)) {
            $missing[] = 'peso';
        }
        if ($title === '') {
            $missing[] = 'título';
        } elseif ($length > self::MELI_TITLE_LIMIT) {
            $missing[] = "título de {$length} caracteres (máx. " . self::MELI_TITLE_LIMIT . ')';
        }
        if ($images === []) {
            $missing[] = 'imágenes';
        }

        return [
            'sku'               => trim((string) ($document['sku'] ?? '')),
            'title'             => $title,
            'title_length'      => $length,
            'images'            => $images,
            'price_usd'         => $price,
            'weight_lb'         => $weight,
            'category'          => $document['categoriaMeli'] ?? null,
            'inserted_at'       => $document['fechaInserccion'] ?? null,
            'missing'           => $missing,
            'publishable'       => $missing === [],
            'final_price_cop'   => null,
            'already_published' => false,
        ];
    }

    private function matches(array $item, CatalogSearchDTO $criteria): bool
    {
        if ($criteria->onlyPublishable && !$item['publishable']) {
            return false;
        }

        return $this->inRange($item['price_usd'], $criteria->minPrice, $criteria->maxPrice)
            && $this->inRange($item['weight_lb'], $criteria->minWeight, $criteria->maxWeight);
    }

    private function inRange(?float $value, ?float $min, ?float $max): bool
    {
        if ($min === null && $max === null) {
            return true;
        }

        return $value !== null
            && ($min === null || $value >= $min)
            && ($max === null || $value <= $max);
    }

    private function withPricesAndPublishedFlags(array $items, ?MercadolibreAccount $account): array
    {
        if ($items === []) {
            return [];
        }

        $settings  = $this->pricingService->settings();
        $published = $account
            ? array_flip(array_map('mb_strtoupper', $this->productRepository->existingSkus($account->id, array_filter(array_column($items, 'sku')))))
            : [];

        return array_map(function (array $item) use ($settings, $published) {
            if ($item['price_usd'] > 0 && $item['weight_lb'] > 0) {
                $item['final_price_cop'] = $this->pricingService->calculateWith($settings, $item['price_usd'], $item['weight_lb'])->finalPrice;
            }

            $item['already_published'] = $item['sku'] !== '' && isset($published[mb_strtoupper($item['sku'])]);

            return $item;
        }, $items);
    }
}
