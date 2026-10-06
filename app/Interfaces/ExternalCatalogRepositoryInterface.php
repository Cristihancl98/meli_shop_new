<?php

namespace App\Interfaces;

interface ExternalCatalogRepositoryInterface
{
    public function findBySku(string $sku): ?array;
    public function findByCategory(string $meliCategoryId): array;
    public function searchByTitle(string $title): array;
    public function currentDollarPrice(): ?float;
}
