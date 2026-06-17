<?php

namespace App\DTOs;

class ProductDTO
{
    public function __construct(
        public readonly string $title,
        public readonly float $price,
        public readonly int $stock,
        public readonly string $status,
        public readonly string $condition,
        public readonly ?string $description = null,
        public readonly ?int $categoryId = null,
        public readonly ?string $listingTypeId = null,
        public readonly ?string $thumbnail = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            title:         $data['title'],
            price:         (float) $data['price'],
            stock:         (int) $data['stock'],
            status:        $data['status'] ?? 'active',
            condition:     $data['condition'] ?? 'new',
            description:   $data['description'] ?? null,
            categoryId:    isset($data['category_id']) ? (int) $data['category_id'] : null,
            listingTypeId: $data['listing_type_id'] ?? null,
            thumbnail:     $data['thumbnail'] ?? null,
        );
    }

    public function toMeliPayload(string $meliCategoryId): array
    {
        $payload = [
            'title'            => $this->title,
            'price'            => $this->price,
            'available_quantity' => $this->stock,
            'condition'        => $this->condition,
            'listing_type_id'  => $this->listingTypeId ?? 'bronze',
            'category_id'      => $meliCategoryId,
            'currency_id'      => 'COP',
        ];

        if ($this->description) {
            $payload['description'] = ['plain_text' => $this->description];
        }

        return $payload;
    }

    public function toArray(): array
    {
        return [
            'title'           => $this->title,
            'price'           => $this->price,
            'stock'           => $this->stock,
            'status'          => $this->status,
            'condition'       => $this->condition,
            'description'     => $this->description,
            'category_id'     => $this->categoryId,
            'listing_type_id' => $this->listingTypeId,
            'thumbnail'       => $this->thumbnail,
        ];
    }
}
