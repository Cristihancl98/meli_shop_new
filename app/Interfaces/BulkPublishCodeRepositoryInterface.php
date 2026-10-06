<?php

namespace App\Interfaces;

use Illuminate\Database\Eloquent\Collection;

interface BulkPublishCodeRepositoryInterface
{
    public function createMany(int $accountId, array $skus): int;
    public function list(int $accountId, ?string $date = null): Collection;
}
