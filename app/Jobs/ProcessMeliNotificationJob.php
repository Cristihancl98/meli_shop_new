<?php

namespace App\Jobs;

use App\Models\MeliNotification;
use App\Models\MercadolibreAccount;
use App\Services\MeliNotificationService;
use App\Services\OrderImportService;
use App\Services\PostSaleService;
use App\Services\QuestionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ProcessMeliNotificationJob implements ShouldQueue
{
    use Queueable;

    public int $tries   = 3;
    public array $backoff = [1, 3, 10];

    public function __construct(
        public readonly MercadolibreAccount $account,
        public readonly string $topic,
        public readonly string $resource,
    ) {}

    public function handle(
        OrderImportService $orderImportService,
        PostSaleService $postSaleService,
        QuestionService $questionService,
        MeliNotificationService $notificationService
    ): void {
        $resourceId = basename(parse_url($this->resource, PHP_URL_PATH) ?: $this->resource);

        $type = match ($this->topic) {
            'orders_v2' => $this->processOrder($orderImportService, $postSaleService, $resourceId),
            'questions' => $questionService->ingest($this->account, $resourceId) ? MeliNotification::TYPE_PRE_SALE_QUESTION : null,
            'messages'  => $postSaleService->ingest($this->account, $resourceId) ? MeliNotification::TYPE_POST_SALE_MESSAGE : null,
            default     => null,
        };

        if ($type) {
            $notificationService->record($this->account, $type, $this->resource);
        }
    }

    private function processOrder(OrderImportService $orderImportService, PostSaleService $postSaleService, string $orderId): ?string
    {
        $order = $orderImportService->importById($this->account, $orderId);

        if (!$order->wasRecentlyCreated) {
            return null;
        }

        $postSaleService->sendSaleMessage($this->account, $order);

        return MeliNotification::TYPE_SALE;
    }

    public function failed(\Throwable $e): void
    {
        Log::error('ProcessMeliNotificationJob falló', [
            'account'  => $this->account->id,
            'topic'    => $this->topic,
            'resource' => $this->resource,
            'error'    => $e->getMessage(),
        ]);
    }
}
