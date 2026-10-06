<?php

namespace App\DTOs;

final class PublishProductDTO
{
    public function __construct(
        public readonly string $sku,
        public readonly string $title,
        public readonly string $meliCategoryId,
        public readonly float $finalPrice,
        public readonly float $basePrice,
        public readonly int $quantity,
        public readonly float $weight,
        public readonly string $brand,
        public readonly array $pictures,
        public readonly string $description,
        public readonly ?float $height = null,
        public readonly ?float $width = null,
        public readonly ?float $length = null,
        public readonly ?string $model = null,
        public readonly ?string $ean = null,
        public readonly array $extraAttributes = [],
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            sku:             $data['sku'],
            title:           $data['title'],
            meliCategoryId:  $data['meli_category_id'],
            finalPrice:      (float) $data['final_price'],
            basePrice:       (float) $data['base_price'],
            quantity:        (int) $data['quantity'],
            weight:          (float) $data['weight'],
            brand:           $data['brand'],
            pictures:        array_values($data['pictures']),
            description:     $data['description'] ?? '',
            height:          isset($data['height']) ? (float) $data['height'] : null,
            width:           isset($data['width']) ? (float) $data['width'] : null,
            length:          isset($data['length']) ? (float) $data['length'] : null,
            model:           $data['model'] ?? null,
            ean:             $data['ean'] ?? null,
            extraAttributes: $data['extra_attributes'] ?? [],
        );
    }

    public function hasDimensions(): bool
    {
        return $this->height > 0 && $this->width > 0 && $this->length > 0;
    }

    public function dimensions(): ?array
    {
        return $this->hasDimensions()
            ? ['height' => $this->height, 'width' => $this->width, 'length' => $this->length]
            : null;
    }

    public function meliAttributes(): array
    {
        $attributes = [
            ['id' => 'BRAND', 'value_name' => $this->brand],
            ['id' => 'WEIGHT', 'value_name' => "{$this->weight} lb"],
        ];

        if ($this->hasDimensions()) {
            $attributes[] = ['id' => 'HEIGHT', 'value_name' => "{$this->height} cm"];
            $attributes[] = ['id' => 'WIDTH', 'value_name' => "{$this->width} cm"];
            $attributes[] = ['id' => 'LENGTH', 'value_name' => "{$this->length} cm"];
        }

        $attributes[] = ['id' => 'SELLER_SKU', 'name' => 'SKU', 'value_name' => $this->sku];

        if ($this->model) {
            $attributes[] = ['id' => 'MODEL', 'value_name' => $this->model];
        }

        if ($this->ean) {
            foreach (['EAN', 'GTIN', 'UPC'] as $code) {
                $attributes[] = ['id' => $code, 'name' => $code, 'value_name' => $this->ean];
            }
        }

        foreach ($this->extraAttributes as $extra) {
            $attributes[] = ['id' => $extra['id'], 'name' => $extra['id'], 'value_name' => $extra['value']];
        }

        return $attributes;
    }

    public function toMeliPayload(string $siteId, string $listingType, int $manufacturingDays, int $warrantyDays): array
    {
        return [
            'site_id'            => $siteId,
            'category_id'        => $this->meliCategoryId,
            'title'              => $this->title,
            'price'              => round($this->finalPrice),
            'currency_id'        => 'COP',
            'available_quantity' => $this->quantity,
            'buying_mode'        => 'buy_it_now',
            'listing_type_id'    => $listingType,
            'condition'          => 'new',
            'pictures'           => array_map(fn (string $url) => ['source' => $url], $this->pictures),
            'attributes'         => $this->meliAttributes(),
            'sale_terms'         => [
                ['id' => 'MANUFACTURING_TIME', 'value_name' => "{$manufacturingDays} días"],
                ['id' => 'WARRANTY_TIME', 'value_name' => "{$warrantyDays} días"],
            ],
        ];
    }
}
