<?php

namespace App\Jobs;

use App\Services\PublishBatchService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ScrapeBatchItemJob implements ShouldQueue
{
    use Queueable;

    public int $tries   = 1;
    public int $timeout = 150;

    public function __construct(public readonly int $itemId) {}

    public function handle(PublishBatchService $service): void
    {
        $service->scrapeItem($this->itemId);
    }

    public function failed(\Throwable $e): void
    {
        app(PublishBatchService::class)->markScrapeFailed($this->itemId, $e->getMessage());
    }
}
