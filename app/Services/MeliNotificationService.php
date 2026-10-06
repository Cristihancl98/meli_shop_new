<?php

namespace App\Services;

use App\Interfaces\MeliNotificationRepositoryInterface;
use App\Models\MeliNotification;
use App\Models\MercadolibreAccount;
use Illuminate\Database\Eloquent\Collection;

class MeliNotificationService
{
    public function __construct(
        private readonly MeliNotificationRepositoryInterface $notificationRepository
    ) {}

    public function unread(MercadolibreAccount $account): Collection
    {
        return $this->notificationRepository->unread($account->id);
    }

    public function record(MercadolibreAccount $account, string $type, ?string $resource = null): MeliNotification
    {
        return $this->notificationRepository->create([
            'mercadolibre_account_id' => $account->id,
            'type'                    => $type,
            'resource'                => $resource,
        ]);
    }

    public function markAsRead(MercadolibreAccount $account, int $id): ?MeliNotification
    {
        $notification = $this->notificationRepository->find($account->id, $id);

        return $notification ? $this->notificationRepository->markAsRead($notification) : null;
    }
}
