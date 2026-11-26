<?php

namespace App\DTOs;

final class CatalogSearchDTO
{
    public const MODE_RECENT   = 'recent';
    public const MODE_TITLE    = 'title';
    public const MODE_CATEGORY = 'category';
    public const MODES         = [self::MODE_RECENT, self::MODE_TITLE, self::MODE_CATEGORY];

    public function __construct(
        public readonly string $mode,
        public readonly string $term = '',
        public readonly ?float $minPrice = null,
        public readonly ?float $maxPrice = null,
        public readonly ?float $minWeight = null,
        public readonly ?float $maxWeight = null,
        public readonly bool $onlyPublishable = true,
    ) {}

    public static function fromArray(array $data): self
    {
        $number = fn (string $key) => isset($data[$key]) && $data[$key] !== '' ? (float) $data[$key] : null;

        return new self(
            mode:            $data['mode'] ?? self::MODE_RECENT,
            term:            trim((string) ($data['q'] ?? '')),
            minPrice:        $number('min_price'),
            maxPrice:        $number('max_price'),
            minWeight:       $number('min_weight'),
            maxWeight:       $number('max_weight'),
            onlyPublishable: (bool) ($data['only_publishable'] ?? false),
        );
    }
}
