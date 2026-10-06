<?php

namespace App\Services;

use App\Interfaces\StoreRepositoryInterface;
use App\Models\Store;
use Illuminate\Support\Facades\DB;

class TenantManager
{
    private ?Store $current = null;

    public function __construct(
        private readonly StoreRepositoryInterface $storeRepository
    ) {}

    public function current(): ?Store
    {
        return $this->current;
    }

    public function activate(Store $store): void
    {
        if ($this->current?->is($store)) {
            return;
        }

        if (config('tenancy.switch_connection')) {
            config(['database.connections.tenant.database' => $store->database]);
            DB::purge('tenant');
        }

        $this->current = $store;
    }

    public function activateById(?int $storeId): ?Store
    {
        $store = $storeId ? $this->storeRepository->findActive($storeId) : null;

        if ($store) {
            $this->activate($store);
        }

        return $store;
    }

    public function activateByCode(string $connectionCode): ?Store
    {
        $store = $this->storeRepository->findActiveByCode(trim($connectionCode));

        if ($store) {
            $this->activate($store);
        }

        return $store;
    }

    public function activateByMeliUserId(string $meliUserId): ?Store
    {
        $store = $this->storeRepository->findActiveByMeliUserId($meliUserId);

        if ($store) {
            $this->activate($store);
        }

        return $store;
    }

    public function forget(): void
    {
        if (config('tenancy.switch_connection')) {
            config(['database.connections.tenant.database' => null]);
            DB::purge('tenant');
        }

        $this->current = null;
    }

    public function runFor(Store $store, callable $callback): mixed
    {
        $previous = $this->current;
        $this->activate($store);

        try {
            return $callback($store);
        } finally {
            $previous ? $this->activate($previous) : $this->forget();
        }
    }

    public function eachActiveStore(callable $callback, ?int $onlyStoreId = null): int
    {
        $stores = $this->storeRepository->active($onlyStoreId);

        foreach ($stores as $store) {
            $this->runFor($store, $callback);
        }

        return $stores->count();
    }

    public function registerMeliAccount(string $meliUserId): void
    {
        if ($this->current) {
            $this->storeRepository->registerMeliAccount($this->current->id, $meliUserId);
        }
    }
}
