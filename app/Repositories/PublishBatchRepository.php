<?php

namespace App\Repositories;

use App\Interfaces\PublishBatchRepositoryInterface;
use App\Models\PublishBatch;
use App\Models\PublishBatchItem;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class PublishBatchRepository implements PublishBatchRepositoryInterface
{
    public function create(int $accountId, ?int $userId, array $items): PublishBatch
    {
        return DB::transaction(function () use ($accountId, $userId, $items) {
            $batch = PublishBatch::create(['mercadolibre_account_id' => $accountId, 'user_id' => $userId]);
            $batch->items()->createMany($items);

            return $batch->load('items');
        });
    }

    public function paginateForAccount(int $accountId, int $perPage = 20): LengthAwarePaginator
    {
        return PublishBatch::with('user')
            ->withCount([
                'items',
                'items as published_count' => fn ($q) => $q->where('status', 'published'),
                'items as ready_count'     => fn ($q) => $q->where('status', 'ready'),
                'items as failed_count'    => fn ($q) => $q->whereIn('status', ['failed', 'not_found']),
                'items as pending_count'   => fn ($q) => $q->whereIn('status', ['queued', 'scraping', 'publishing']),
            ])
            ->where('mercadolibre_account_id', $accountId)
            ->latest('id')
            ->paginate($perPage);
    }

    public function findForAccount(int $accountId, int $batchId): ?PublishBatch
    {
        return PublishBatch::with(['items.product', 'user'])
            ->where('mercadolibre_account_id', $accountId)
            ->find($batchId);
    }

    public function findItem(int $itemId): ?PublishBatchItem
    {
        return PublishBatchItem::with('batch.account')->find($itemId);
    }

    public function findItemForAccount(int $accountId, int $itemId): ?PublishBatchItem
    {
        return PublishBatchItem::with('batch')
            ->whereHas('batch', fn ($q) => $q->where('mercadolibre_account_id', $accountId))
            ->find($itemId);
    }

    public function itemsByIds(PublishBatch $batch, array $itemIds): Collection
    {
        return $batch->items()->whereIn('id', $itemIds)->get();
    }

    public function updateItem(PublishBatchItem $item, array $data): PublishBatchItem
    {
        $item->update($data);

        return $item;
    }

    public function statusCounts(PublishBatch $batch): array
    {
        return $batch->items()->reorder()->toBase()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->map(fn ($total) => (int) $total)
            ->all();
    }
}
