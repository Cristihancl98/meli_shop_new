<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Order\UpdateFinalPriceRequest;
use App\Traits\ResolvesMeliAccount;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class OrderController extends Controller
{
    use ResolvesMeliAccount;

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

    public function updateFinalPrice(UpdateFinalPriceRequest $request, int $id): RedirectResponse
    {
        $order = $this->resolveForAccount($id, 'updateFinalPrice');
        $this->orderService->setFinalPrice($order, (float) $request->validated('final_price'));

        return back()->with('success', 'Precio final guardado.');
    }

    public function shippingLabel(int $id): Response
    {
        $order = $this->resolveForAccount($id, 'downloadLabel');

        return response($this->orderService->shippingLabel($this->currentAccount(), $order), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="etiqueta-' . $order->meli_order_id . '.pdf"',
        ]);
    }

    private function resolveForAccount(int $id, string $ability): Order
    {
        $account = $this->currentAccount();
        $order   = $account ? $this->orderService->findForAccount($account, $id) : null;
        abort_if(!$order, 404);

        $this->authorize($ability, $order);

        return $order;
    }
}
