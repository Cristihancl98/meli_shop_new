<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\MeliNotification;
use App\Services\MeliNotificationService;
use App\Traits\ResolvesMeliAccount;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class NotificationController extends Controller
{
    use ResolvesMeliAccount;

    public function __construct(private readonly MeliNotificationService $notificationService) {}

    public function index(): View
    {
        $this->authorize('viewAny', MeliNotification::class);

        $account       = $this->currentAccount();
        $notifications = $account ? $this->notificationService->unread($account) : collect();

        return view('notifications.index', compact('account', 'notifications'));
    }

    public function read(int $id): RedirectResponse
    {
        $this->authorize('update', MeliNotification::class);

        $account = $this->currentAccount();
        abort_if(!$account || !$this->notificationService->markAsRead($account, $id), 404);

        return back();
    }
}
