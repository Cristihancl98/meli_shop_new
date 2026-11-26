<?php

namespace App\DTOs;

final class CatalogSearchResultDTO
{
    public function __construct(
        public readonly array $items,
        public readonly int $scanned,
        public readonly bool $limitReached,
    ) {}

    public function publishableCount(): int
    {
        return count(array_filter($this->items, fn (array $item) => $item['publishable'] && !$item['already_published']));
    }
}
