<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Order\UpdateFinalPriceRequest;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Traits\ApiResponses;
use App\Traits\ResolvesMeliAccount;

class OrderController extends Controller
{
    use ApiResponses, ResolvesMeliAccount;

    public function __construct(private readonly OrderService $orderService) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', \App\Models\Order::class);

        $filters = $request->only([
            'status', 'payment_status', 'date_from', 'date_to',
            'customer_name', 'min_amount', 'max_amount',
        ]);

        $orders = $this->orderService->getPaginated(auth()->id(), $filters);

        return response()->json([
            'success' => true,
            'data'    => $orders,
            'message' => 'Ventas obtenidas correctamente',
            'errors'  => null,
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $order = $this->orderService->findById($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'data'    => null,
                'message' => 'Venta no encontrada',
                'errors'  => null,
            ], 404);
        }

        $this->authorize('view', $order);

        return response()->json([
            'success' => true,
            'data'    => $order,
            'message' => 'Venta obtenida correctamente',
            'errors'  => null,
        ]);
    }

    public function sync(Request $request): JsonResponse
    {
        $this->authorize('sync', \App\Models\Order::class);

        $result = $this->orderService->syncFromMeli(auth()->id(), $request->only(['date_from', 'date_to']));

        return response()->json([
            'success' => empty($result['errors']),
            'data'    => ['synced' => $result['synced']],
            'message' => "Se sincronizaron {$result['synced']} ventas",
            'errors'  => $result['errors'] ?: null,
        ]);
    }

    public function updateFinalPrice(UpdateFinalPriceRequest $request, int $id): JsonResponse
    {
        if (!$account = $this->currentAccount()) {
            return $this->noAccount();
        }

        if (!$order = $this->orderService->findForAccount($account, $id)) {
            return $this->notFound('Venta no encontrada');
        }

        $this->authorize('updateFinalPrice', $order);

        return $this->ok($this->orderService->setFinalPrice($order, (float) $request->validated('final_price')), 'Precio actualizado correctamente');
    }

    public function shippingLabel(int $id): Response|JsonResponse
    {
        if (!$account = $this->currentAccount()) {
            return $this->noAccount();
        }

        if (!$order = $this->orderService->findForAccount($account, $id)) {
            return $this->notFound('Venta no encontrada');
        }

        $this->authorize('downloadLabel', $order);

        return response($this->orderService->shippingLabel($account, $order), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="etiqueta-' . $order->meli_order_id . '.pdf"',
        ]);
    }
}
