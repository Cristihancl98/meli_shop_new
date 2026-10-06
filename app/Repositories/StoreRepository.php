<?php

namespace App\Repositories;

use App\Interfaces\StoreRepositoryInterface;
use App\Models\Store;
use App\Models\StoreMeliAccount;
use Illuminate\Database\Eloquent\Collection;

class StoreRepository implements StoreRepositoryInterface
{
    public function find(int $id): ?Store
    {
        return Store::find($id);
    }

    public function findActive(int $id): ?Store
    {
        return Store::where('is_active', true)->find($id);
    }

    public function findActiveByCode(string $connectionCode): ?Store
    {
        return Store::where('connection_code', $connectionCode)->where('is_active', true)->first();
    }

    public function findActiveByMeliUserId(string $meliUserId): ?Store
    {
        return Store::where('is_active', true)
            ->whereHas('meliAccounts', fn ($q) => $q->where('meli_user_id', $meliUserId))
            ->first();
    }

    public function active(?int $onlyId = null): Collection
    {
        return Store::where('is_active', true)
            ->when($onlyId, fn ($q) => $q->where('id', $onlyId))
            ->orderBy('id')
            ->get();
    }

    public function isDatabaseInUse(string $database): bool
    {
        return Store::all(['id', 'database'])->contains(fn (Store $store) => $store->database === $database);
    }

    public function create(array $data): Store
    {
        return Store::create($data);
    }

    public function registerMeliAccount(int $storeId, string $meliUserId): void
    {
        StoreMeliAccount::updateOrCreate(['meli_user_id' => $meliUserId], ['store_id' => $storeId]);
    }
}
