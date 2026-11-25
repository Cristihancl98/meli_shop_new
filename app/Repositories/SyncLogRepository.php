<?php

namespace App\Repositories;

use App\Interfaces\SyncLogRepositoryInterface;
use App\Models\SyncLog;
use Illuminate\Database\Eloquent\Builder;

class SyncLogRepository implements SyncLogRepositoryInterface
{
    public function create(array $data): SyncLog
    {
        return SyncLog::create($data);
    }

    public function find(int $id): ?SyncLog
    {
        return SyncLog::find($id);
    }

    public function update(SyncLog $log, array $data): SyncLog
    {
        $log->update($data);

        return $log;
    }

    public function latestByPrefix(int $accountId, string $type, string $messagePrefix): ?SyncLog
    {
        return $this->byPrefix($accountId, $type, $messagePrefix)->latest('id')->first();
    }

    public function activeByPrefix(int $accountId, string $type, string $messagePrefix, int $withinMinutes): ?SyncLog
    {
        return $this->byPrefix($accountId, $type, $messagePrefix)
            ->whereIn('status', ['pending', 'running'])
            ->where('created_at', '>=', now()->subMinutes($withinMinutes))
            ->latest('id')
            ->first();
    }

    private function byPrefix(int $accountId, string $type, string $messagePrefix): Builder
    {
        return SyncLog::where('mercadolibre_account_id', $accountId)
            ->where('type', $type)
            ->where('message', 'like', $messagePrefix . '%');
    }
}
