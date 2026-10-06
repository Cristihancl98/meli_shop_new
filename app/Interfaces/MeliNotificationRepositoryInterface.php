<?php

namespace App\Interfaces;

use App\Models\MeliNotification;
use Illuminate\Database\Eloquent\Collection;

interface MeliNotificationRepositoryInterface
{
    public function unread(int $accountId): Collection;
    public function find(int $accountId, int $id): ?MeliNotification;
    public function create(array $data): MeliNotification;
    public function markAsRead(MeliNotification $notification): MeliNotification;
}
