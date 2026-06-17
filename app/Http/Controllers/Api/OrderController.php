<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
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
}
