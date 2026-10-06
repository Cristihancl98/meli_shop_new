<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MeliNotification;
use App\Services\MeliNotificationService;
use App\Traits\ApiResponses;
use App\Traits\ResolvesMeliAccount;
use Illuminate\Http\JsonResponse;

class NotificationController extends Controller
{
    use ApiResponses, ResolvesMeliAccount;

    public function __construct(private readonly MeliNotificationService $notificationService) {}

    public function index(): JsonResponse
    {
        $this->authorize('viewAny', MeliNotification::class);

        if (!$account = $this->currentAccount()) {
            return $this->noAccount();
        }

        return $this->ok($this->notificationService->unread($account), 'Notificaciones obtenidas.');
    }

    public function markAsRead(int $id): JsonResponse
    {
        $this->authorize('update', MeliNotification::class);

        if (!$account = $this->currentAccount()) {
            return $this->noAccount();
        }

        $notification = $this->notificationService->markAsRead($account, $id);

        return $notification
            ? $this->ok($notification, 'Notificación marcada como leída.')
            : $this->notFound('Notificación no encontrada.');
    }
}
