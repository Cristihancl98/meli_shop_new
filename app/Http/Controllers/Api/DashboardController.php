<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function __construct(private readonly DashboardService $dashboardService) {}

    public function index(): JsonResponse
    {
        $metrics = $this->dashboardService->getMetrics(auth()->id());

        return response()->json([
            'success' => true,
            'data'    => $metrics,
            'message' => 'Métricas obtenidas correctamente',
            'errors'  => null,
        ]);
    }

    public function topProducts(): JsonResponse
    {
        $products = $this->dashboardService->getTopProducts(auth()->id());

        return response()->json([
            'success' => true,
            'data'    => $products,
            'message' => 'Top productos obtenidos correctamente',
            'errors'  => null,
        ]);
    }

    public function salesSummary(): JsonResponse
    {
        $daily   = $this->dashboardService->getDailySales(auth()->id(), 30);
        $monthly = $this->dashboardService->getMonthlyRevenue(auth()->id(), 6);

        return response()->json([
            'success' => true,
            'data'    => compact('daily', 'monthly'),
            'message' => 'Resumen de ventas obtenido correctamente',
            'errors'  => null,
        ]);
    }

    public function alerts(): JsonResponse
    {
        $alerts = $this->dashboardService->getAlerts(auth()->id());

        return response()->json([
            'success' => true,
            'data'    => $alerts,
            'message' => 'Alertas obtenidas correctamente',
            'errors'  => null,
        ]);
    }
}
