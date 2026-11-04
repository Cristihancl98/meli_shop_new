<?php

namespace App\Exceptions;

use RuntimeException;

class ScrapingRequiredException extends RuntimeException
{
    public function __construct(public readonly string $sku, public readonly string $scraperUrl)
    {
        parent::__construct("El SKU {$sku} no está en el catálogo y debe consultarse en el scraper.");
    }
}
