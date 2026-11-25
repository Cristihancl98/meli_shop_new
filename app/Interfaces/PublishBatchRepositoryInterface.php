<?php

namespace App\Interfaces;

use App\Models\PublishBatch;
use App\Models\PublishBatchItem;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface PublishBatchRepositoryInterface
{
    public function create(int $accountId, ?int $userId, array $items): PublishBatch;
    public function paginateForAccount(int $accountId, int $perPage = 20): LengthAwarePaginator;
    public function findForAccount(int $accountId, int $batchId): ?PublishBatch;
    public function findItem(int $itemId): ?PublishBatchItem;
    public function findItemForAccount(int $accountId, int $itemId): ?PublishBatchItem;
    public function itemsByIds(PublishBatch $batch, array $itemIds): Collection;
    public function updateItem(PublishBatchItem $item, array $data): PublishBatchItem;
    public function statusCounts(PublishBatch $batch): array;
}
