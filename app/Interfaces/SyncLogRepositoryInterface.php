<?php

namespace App\Interfaces;

use App\Models\SyncLog;

interface SyncLogRepositoryInterface
{
    public function create(array $data): SyncLog;
    public function find(int $id): ?SyncLog;
    public function update(SyncLog $log, array $data): SyncLog;
    public function latestByPrefix(int $accountId, string $type, string $messagePrefix): ?SyncLog;
    public function activeByPrefix(int $accountId, string $type, string $messagePrefix, int $withinMinutes): ?SyncLog;
}
