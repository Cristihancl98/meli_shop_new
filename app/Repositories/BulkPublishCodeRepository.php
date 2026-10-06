<?php

namespace App\Repositories;

use App\Interfaces\BulkPublishCodeRepositoryInterface;
use App\Models\BulkPublishCode;
use Illuminate\Database\Eloquent\Collection;

class BulkPublishCodeRepository implements BulkPublishCodeRepositoryInterface
{
    public function createMany(int $accountId, array $skus): int
    {
        $now  = now();
        $rows = array_map(fn (string $sku) => [
            'mercadolibre_account_id' => $accountId,
            'sku'                     => $sku,
            'status'                  => 'pending',
            'created_at'              => $now,
            'updated_at'              => $now,
        ], $skus);

        BulkPublishCode::insert($rows);

        return count($rows);
    }

    public function list(int $accountId, ?string $date = null): Collection
    {
        $query = BulkPublishCode::where('mercadolibre_account_id', $accountId);

        $date
            ? $query->whereDate('created_at', $date)
            : $query->where('status', 'pending');

        return $query->latest()->get();
    }
}
