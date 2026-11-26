<?php

namespace App\Enums;

enum PriceReviewReason: string
{
    case PriceDrop    = 'price_drop';
    case NotOnAmazon  = 'not_on_amazon';
    case UpdateFailed = 'update_failed';

    public function label(): string
    {
        return match ($this) {
            self::PriceDrop    => 'Amazon bajó el precio',
            self::NotOnAmazon  => 'Ya no existe en Amazon',
            self::UpdateFailed => 'No se pudo actualizar',
        };
    }

    public function badge(): string
    {
        return match ($this) {
            self::PriceDrop    => 'pending',
            self::NotOnAmazon  => 'paused',
            self::UpdateFailed => 'cancelled',
        };
    }
}
