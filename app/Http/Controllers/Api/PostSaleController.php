<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PostSale\ReplyPostSaleRequest;
use App\Models\PostSaleMessage;
use App\Services\OrderService;
use App\Services\PostSaleService;
use App\Traits\ApiResponses;
use App\Traits\ResolvesMeliAccount;
use Illuminate\Http\JsonResponse;

class PostSaleController extends Controller
{
    use ApiResponses, ResolvesMeliAccount;

    public function __construct(
        private readonly PostSaleService $postSaleService,
        private readonly OrderService $orderService
    ) {}

    public function index(): JsonResponse
    {
        $this->authorize('viewAny', PostSaleMessage::class);

        if (!$account = $this->currentAccount()) {
            return $this->noAccount();
        }

        return $this->ok($this->postSaleService->conversations($account), 'Conversaciones posventa obtenidas.');
    }

    public function reply(ReplyPostSaleRequest $request, int $orderId): JsonResponse
    {
        $this->authorize('create', PostSaleMessage::class);

        if (!$account = $this->currentAccount()) {
            return $this->noAccount();
        }

        if (!$order = $this->orderService->findForAccount($account, $orderId)) {
            return $this->notFound('Venta no encontrada.');
        }

        $message = $this->postSaleService->reply($account, $order, $request->validated('text'), $request->user());

        return $this->ok($message, 'Mensaje enviado.', 201);
    }
}
