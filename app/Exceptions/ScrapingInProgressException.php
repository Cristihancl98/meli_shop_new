<?php

namespace App\Exceptions;

use RuntimeException;

class ScrapingInProgressException extends RuntimeException
{
    public function __construct(public readonly string $sku)
    {
        parent::__construct("El SKU {$sku} se está consultando en Amazon.");
    }
}
