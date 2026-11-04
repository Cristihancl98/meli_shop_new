<?php

namespace App\Jobs;

use App\Services\CatalogService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class ScrapeSkuJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 300;

    public function __construct(public readonly string $sku) {}

    public function handle(CatalogService $catalogService): void
    {
        $catalogService->runScraping($this->sku);
    }

    public function failed(?Throwable $exception): void
    {
        app(CatalogService::class)->markScrapingFailed($this->sku);
    }
}
