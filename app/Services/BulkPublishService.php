<?php

namespace App\Services;

use App\Interfaces\BulkPublishCodeRepositoryInterface;
use App\Models\MercadolibreAccount;
use Illuminate\Database\Eloquent\Collection;

class BulkPublishService
{
    public function __construct(
        private readonly BulkPublishCodeRepositoryInterface $codeRepository
    ) {}

    public function addCodes(MercadolibreAccount $account, array $skus): int
    {
        $clean = array_values(array_unique(array_filter(array_map('trim', $skus))));

        return $this->codeRepository->createMany($account->id, $clean);
    }

    public function list(MercadolibreAccount $account, ?string $date = null): Collection
    {
        return $this->codeRepository->list($account->id, $date);
    }
}
