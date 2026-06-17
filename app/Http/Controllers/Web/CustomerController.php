<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Services\CustomerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function __construct(private readonly CustomerService $customerService) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Customer::class);

        $filters   = $request->only(['search']);
        $customers = $this->customerService->getPaginated(auth()->id(), $filters, session('active_meli_account_id'));

        return view('customers.index', compact('customers', 'filters'));
    }

    public function show(int $id): View
    {
        $customer = $this->customerService->findById($id);

        if (!$customer) {
            abort(404);
        }

        $this->authorize('view', $customer);

        $totalSpent  = $customer->orders->where('status', 'paid')->sum('total_amount');
        $totalOrders = $customer->orders->count();

        return view('customers.show', compact('customer', 'totalSpent', 'totalOrders'));
    }
}
