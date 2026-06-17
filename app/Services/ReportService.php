<?php

namespace App\Services;

use App\Exports\CustomersExport;
use App\Exports\ProductsExport;
use App\Exports\SalesExport;
use App\Interfaces\CustomerRepositoryInterface;
use App\Interfaces\MercadolibreAccountRepositoryInterface;
use App\Interfaces\ProductRepositoryInterface;
use App\Models\Order;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReportService
{
    public function __construct(
        private readonly ProductRepositoryInterface              $productRepository,
        private readonly CustomerRepositoryInterface            $customerRepository,
        private readonly MercadolibreAccountRepositoryInterface $accountRepository,
    ) {}

    public function getSalesByDateRange(int $userId, ?string $dateFrom, ?string $dateTo, ?int $selectedAccountId = null): array
    {
        $account = $this->accountRepository->findSelectedByUser($userId, $selectedAccountId ?? null);
        if (!$account) {
            return ['rows' => collect(), 'total_revenue' => 0, 'total_orders' => 0, 'paid_orders' => 0];
        }

        $query = Order::with(['customer', 'items'])
            ->where('mercadolibre_account_id', $account->id)
            ->orderBy('order_date', 'desc');

        if ($dateFrom) {
            $query->whereDate('order_date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->whereDate('order_date', '<=', $dateTo);
        }

        $orders = $query->get();

        return [
            'rows'          => $orders,
            'total_revenue' => $orders->where('status', 'paid')->sum('total_amount'),
            'total_orders'  => $orders->count(),
            'paid_orders'   => $orders->where('status', 'paid')->count(),
        ];
    }

    public function getTopProducts(int $userId, int $limit = 20, ?int $selectedAccountId = null): Collection
    {
        $account = $this->accountRepository->findSelectedByUser($userId, $selectedAccountId ?? null);
        if (!$account) {
            return collect();
        }

        return $this->productRepository->topSelling($account->id, $limit);
    }

    public function getTopCustomers(int $userId, int $limit = 20, ?int $selectedAccountId = null): Collection
    {
        $account = $this->accountRepository->findSelectedByUser($userId, $selectedAccountId ?? null);
        if (!$account) {
            return collect();
        }

        return $this->customerRepository->topBuyers($account->id, $limit);
    }

    public function exportSales(int $userId, ?string $dateFrom, ?string $dateTo): BinaryFileResponse
    {
        $data     = $this->getSalesByDateRange($userId, $dateFrom, $dateTo);
        $filename = 'ventas_' . now()->format('Ymd_His') . '.xlsx';

        return Excel::download(new SalesExport($data['rows']), $filename);
    }

    public function exportProducts(int $userId): BinaryFileResponse
    {
        $products = $this->getTopProducts($userId, 500);
        $filename = 'productos_' . now()->format('Ymd_His') . '.xlsx';

        return Excel::download(new ProductsExport($products), $filename);
    }

    public function exportCustomers(int $userId): BinaryFileResponse
    {
        $customers = $this->getTopCustomers($userId, 500);
        $filename  = 'clientes_' . now()->format('Ymd_His') . '.xlsx';

        return Excel::download(new CustomersExport($customers), $filename);
    }
}
