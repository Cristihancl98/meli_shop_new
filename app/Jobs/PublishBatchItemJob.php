<?php

namespace App\Jobs;

use App\Services\PublishBatchService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class PublishBatchItemJob implements ShouldQueue
{
    use Queueable;

    public int $tries   = 1;
    public int $timeout = 180;

    public function __construct(
        public readonly int $itemId,
        public readonly int $userId
    ) {}

    public function handle(PublishBatchService $service): void
    {
        $service->publishItem($this->itemId, $this->userId);
    }

    public function failed(\Throwable $e): void
    {
        app(PublishBatchService::class)->markPublishFailed($this->itemId, $e->getMessage());
    }
}
