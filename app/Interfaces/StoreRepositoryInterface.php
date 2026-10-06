<?php

namespace App\Interfaces;

use App\Models\Store;
use Illuminate\Database\Eloquent\Collection;

interface StoreRepositoryInterface
{
    public function find(int $id): ?Store;
    public function findActive(int $id): ?Store;
    public function findActiveByCode(string $connectionCode): ?Store;
    public function findActiveByMeliUserId(string $meliUserId): ?Store;
    public function active(?int $onlyId = null): Collection;
    public function isDatabaseInUse(string $database): bool;
    public function create(array $data): Store;
    public function registerMeliAccount(int $storeId, string $meliUserId): void;
}
