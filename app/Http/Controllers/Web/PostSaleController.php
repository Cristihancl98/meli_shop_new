<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\PostSale\ReplyPostSaleRequest;
use App\Models\PostSaleMessage;
use App\Services\OrderService;
use App\Services\PostSaleService;
use App\Traits\ResolvesMeliAccount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;

class PostSaleController extends Controller
{
    use ResolvesMeliAccount;

    public function __construct(
        private readonly PostSaleService $postSaleService,
        private readonly OrderService $orderService
    ) {}

    public function index(): View
    {
        $this->authorize('viewAny', PostSaleMessage::class);

        $account       = $this->currentAccount();
        $conversations = $account ? $this->postSaleService->conversations($account) : new LengthAwarePaginator([], 0, 20);

        return view('post-sale.index', compact('account', 'conversations'));
    }

    public function reply(ReplyPostSaleRequest $request, int $orderId): RedirectResponse
    {
        $this->authorize('create', PostSaleMessage::class);

        $account = $this->currentAccount();
        $order   = $account ? $this->orderService->findForAccount($account, $orderId) : null;
        abort_if(!$order, 404);

        $this->postSaleService->reply($account, $order, $request->validated('text'), $request->user());

        return back()->with('success', 'Mensaje enviado al comprador.');
    }
}
