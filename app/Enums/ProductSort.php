<?php

namespace App\Enums;

enum ProductSort: string
{
    case Updated   = 'updated';
    case Published = 'published';
    case Title     = 'title';
    case Sku       = 'sku';
    case Price     = 'price';
    case Stock     = 'stock';
    case Sold      = 'sold';
    case Status    = 'status';

    public const DEFAULT = self::Updated;

    public function label(): string
    {
        return match ($this) {
            self::Updated   => 'Última actualización',
            self::Published => 'Fecha de publicación',
            self::Title     => 'Título',
            self::Sku       => 'SKU',
            self::Price     => 'Precio',
            self::Stock     => 'Stock',
            self::Sold      => 'Vendidos',
            self::Status    => 'Estado',
        };
    }

    public function defaultDirection(): string
    {
        return in_array($this, [self::Title, self::Sku, self::Status], true) ? 'asc' : 'desc';
    }
}
