<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function __construct(private readonly ReportService $reportService) {}

    public function sales(Request $request): JsonResponse
    {
        $this->authorize('view-report');

        $data = $this->reportService->getSalesByDateRange(
            auth()->id(),
            $request->input('date_from'),
            $request->input('date_to'),
        );

        return response()->json([
            'success' => true,
            'data'    => $data,
            'message' => 'Reporte de ventas generado correctamente',
            'errors'  => null,
        ]);
    }

    public function topProducts(): JsonResponse
    {
        $this->authorize('view-report');

        $products = $this->reportService->getTopProducts(auth()->id());

        return response()->json([
            'success' => true,
            'data'    => $products,
            'message' => 'Top productos obtenidos correctamente',
            'errors'  => null,
        ]);
    }

    public function topCustomers(): JsonResponse
    {
        $this->authorize('view-report');

        $customers = $this->reportService->getTopCustomers(auth()->id());

        return response()->json([
            'success' => true,
            'data'    => $customers,
            'message' => 'Top clientes obtenidos correctamente',
            'errors'  => null,
        ]);
    }

    public function exportSales(Request $request)
    {
        $this->authorize('export-report');

        return $this->reportService->exportSales(
            auth()->id(),
            $request->input('date_from'),
            $request->input('date_to'),
        );
    }

    public function exportProducts()
    {
        $this->authorize('export-report');

        return $this->reportService->exportProducts(auth()->id());
    }

    public function exportCustomers()
    {
        $this->authorize('export-report');

        return $this->reportService->exportCustomers(auth()->id());
    }
}
