<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CustomerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function __construct(private readonly CustomerService $customerService) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', \App\Models\Customer::class);

        $filters   = $request->only(['search']);
        $customers = $this->customerService->getPaginated(auth()->id(), $filters);

        return response()->json([
            'success' => true,
            'data'    => $customers,
            'message' => 'Clientes obtenidos correctamente',
            'errors'  => null,
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $customer = $this->customerService->findById($id);

        if (!$customer) {
            return response()->json([
                'success' => false,
                'data'    => null,
                'message' => 'Cliente no encontrado',
                'errors'  => null,
            ], 404);
        }

        $this->authorize('view', $customer);

        return response()->json([
            'success' => true,
            'data'    => $customer,
            'message' => 'Cliente obtenido correctamente',
            'errors'  => null,
        ]);
    }

    public function orders(int $id): JsonResponse
    {
        $customer = $this->customerService->findById($id);

        if (!$customer) {
            return response()->json([
                'success' => false,
                'data'    => null,
                'message' => 'Cliente no encontrado',
                'errors'  => null,
            ], 404);
        }

        $this->authorize('view', $customer);

        return response()->json([
            'success' => true,
            'data'    => $customer->orders,
            'message' => 'Órdenes del cliente obtenidas correctamente',
            'errors'  => null,
        ]);
    }
}
