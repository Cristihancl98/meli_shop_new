<?php

namespace App\DTOs;

final class UpdateListingDTO
{
    public function __construct(
        public readonly ?string $title = null,
        public readonly ?float $price = null,
        public readonly ?float $basePrice = null,
        public readonly ?int $quantity = null,
        public readonly ?array $pictures = null,
        public readonly ?string $description = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            title:       $data['title'] ?? null,
            price:       isset($data['price']) ? (float) $data['price'] : null,
            basePrice:   isset($data['base_price']) ? (float) $data['base_price'] : null,
            quantity:    isset($data['quantity']) ? (int) $data['quantity'] : null,
            pictures:    isset($data['pictures']) ? array_values($data['pictures']) : null,
            description: $data['description'] ?? null,
        );
    }

    public function meliItemPayload(): array
    {
        return array_filter([
            'title'              => $this->title,
            'price'              => $this->price !== null ? round($this->price) : null,
            'available_quantity' => $this->quantity,
            'pictures'           => $this->pictures !== null
                ? array_map(fn (string $url) => ['source' => $url], $this->pictures)
                : null,
        ], fn ($value) => $value !== null);
    }

    public function isEmpty(): bool
    {
        return $this->meliItemPayload() === [] && $this->description === null;
    }
}
