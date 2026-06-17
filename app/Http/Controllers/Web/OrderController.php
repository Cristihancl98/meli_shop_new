<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function __construct(private readonly OrderService $orderService) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Order::class);

        $filters = $request->only([
            'status', 'payment_status', 'date_from', 'date_to',
            'customer_name', 'min_amount', 'max_amount',
        ]);

        $orders = $this->orderService->getPaginated(
            auth()->id(),
            $filters,
            session('active_meli_account_id')
        );

        return view('orders.index', compact('orders', 'filters'));
    }

    public function show(int $id): View
    {
        $order = $this->orderService->findById($id);

        if (!$order) {
            abort(404);
        }

        $this->authorize('view', $order);

        return view('orders.show', compact('order'));
    }

    public function sync(Request $request): RedirectResponse
    {
        $this->authorize('sync', Order::class);

        $result = $this->orderService->syncFromMeli(
            auth()->id(),
            $request->only(['date_from', 'date_to']),
            session('active_meli_account_id')
        );

        if (!empty($result['errors'])) {
            return redirect()->route('orders.index')
                ->with('error', 'Sincronización parcial: ' . implode(', ', $result['errors']));
        }

        return redirect()->route('orders.index')
            ->with('success', "Se sincronizaron {$result['synced']} ventas desde Mercado Libre");
    }
}
