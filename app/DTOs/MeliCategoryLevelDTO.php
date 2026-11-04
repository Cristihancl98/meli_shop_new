<?php

namespace App\DTOs;

final class MeliCategoryLevelDTO
{
    /**
     * @param array<int, array{id: string, name: string}> $path
     * @param array<int, array{id: string, name: string}> $children
     */
    public function __construct(
        public readonly ?string $id,
        public readonly ?string $name,
        public readonly array $path,
        public readonly array $children,
    ) {}

    public function isLeaf(): bool
    {
        return $this->id !== null && $this->children === [];
    }

    public function toArray(): array
    {
        return [
            'id'       => $this->id,
            'name'     => $this->name,
            'path'     => $this->path,
            'children' => $this->children,
            'is_leaf'  => $this->isLeaf(),
        ];
    }
}
