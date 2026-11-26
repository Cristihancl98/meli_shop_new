<?php

namespace App\Interfaces;

interface ExternalCatalogRepositoryInterface
{
    public function findBySku(string $sku): ?array;
    public function findManyBySkus(array $skus): array;
    public function findByCategory(string $meliCategoryId): array;
    public function searchByTitle(string $title): array;
    public function currentDollarPrice(): ?float;
    public function iterate(string $mode, string $term = ''): iterable;
}
