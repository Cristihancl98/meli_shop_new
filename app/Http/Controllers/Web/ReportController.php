<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function __construct(private readonly ReportService $reportService) {}

    public function sales(Request $request): View
    {
        $this->authorize('view-report');

        $dateFrom  = $request->input('date_from', now()->startOfMonth()->toDateString());
        $dateTo    = $request->input('date_to', now()->toDateString());
        $accountId = session('active_meli_account_id');

        $data = $this->reportService->getSalesByDateRange(auth()->id(), $dateFrom, $dateTo, $accountId);

        return view('reports.sales', array_merge($data, compact('dateFrom', 'dateTo')));
    }

    public function products(): View
    {
        $this->authorize('view-report');

        $products = $this->reportService->getTopProducts(auth()->id(), 20, session('active_meli_account_id'));

        return view('reports.products', compact('products'));
    }

    public function customers(): View
    {
        $this->authorize('view-report');

        $customers = $this->reportService->getTopCustomers(auth()->id(), 20, session('active_meli_account_id'));

        return view('reports.customers', compact('customers'));
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
