<?php

namespace Tests\Fakes;

use App\Interfaces\ExternalCatalogRepositoryInterface;

class FakeCatalogRepository implements ExternalCatalogRepositoryInterface
{
    public array $products = [];
    public ?float $dollar = null;

    public function findBySku(string $sku): ?array
    {
        $wanted = strtoupper(trim($sku));

        foreach ($this->products as $product) {
            if (strtoupper(trim(preg_replace('/\p{Cf}+/u', '', $product['sku'] ?? ''))) === $wanted) {
                return $product;
            }
        }

        return null;
    }

    public function findByCategory(string $meliCategoryId): array
    {
        return array_values(array_filter($this->products, fn ($p) => ($p['categoriaMeli'] ?? null) === $meliCategoryId));
    }

    public function searchByTitle(string $title): array
    {
        return array_values(array_filter($this->products, fn ($p) => mb_stripos($p['titulo'] ?? '', $title) !== false));
    }

    public function currentDollarPrice(): ?float
    {
        return $this->dollar;
    }
}
