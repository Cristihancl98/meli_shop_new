<?php

namespace App\Repositories;

use App\Interfaces\MeliNotificationRepositoryInterface;
use App\Models\MeliNotification;
use Illuminate\Database\Eloquent\Collection;

class MeliNotificationRepository implements MeliNotificationRepositoryInterface
{
    public function unread(int $accountId): Collection
    {
        return MeliNotification::where('mercadolibre_account_id', $accountId)
            ->whereNull('read_at')
            ->latest()
            ->get();
    }

    public function find(int $accountId, int $id): ?MeliNotification
    {
        return MeliNotification::where('mercadolibre_account_id', $accountId)->find($id);
    }

    public function create(array $data): MeliNotification
    {
        return MeliNotification::create($data);
    }

    public function markAsRead(MeliNotification $notification): MeliNotification
    {
        $notification->update(['read_at' => now()]);
        return $notification;
    }
}
