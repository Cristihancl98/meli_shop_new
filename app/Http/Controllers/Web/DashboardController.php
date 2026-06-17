<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private readonly DashboardService $dashboardService) {}

    public function index(): View
    {
        $userId     = auth()->id();
        $accountId  = session('active_meli_account_id');

        $metrics        = $this->dashboardService->getMetrics($userId, $accountId);
        $topProducts    = $this->dashboardService->getTopProducts($userId, 10, $accountId);
        $recentOrders   = $this->dashboardService->getRecentOrders($userId, 20, $accountId);
        $alerts         = $this->dashboardService->getAlerts($userId, $accountId);
        $dailySales     = $this->dashboardService->getDailySales($userId, 30, $accountId);
        $monthlyRevenue = $this->dashboardService->getMonthlyRevenue($userId, 6, $accountId);

        return view('dashboard.index', compact(
            'metrics',
            'topProducts',
            'recentOrders',
            'alerts',
            'dailySales',
            'monthlyRevenue',
        ));
    }
}
