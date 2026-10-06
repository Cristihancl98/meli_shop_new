<?php

namespace App\Services;

use App\DTOs\OrderDTO;
use App\Exceptions\BusinessRuleException;
use App\Exceptions\MeliApiException;
use App\Interfaces\MercadolibreAccountRepositoryInterface;
use App\Interfaces\OrderRepositoryInterface;
use App\Models\Customer;
use App\Models\MercadolibreAccount;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderService
{
    public function __construct(
        private readonly OrderRepositoryInterface              $orderRepository,
        private readonly MercadolibreAccountRepositoryInterface $accountRepository,
        private readonly MercadoLibreService                  $meliService,
    ) {}

    public function getPaginated(int $userId, array $filters, ?int $selectedAccountId = null): LengthAwarePaginator
    {
        $account = $this->accountRepository->findSelectedByUser($userId, $selectedAccountId);
        if ($account) {
            $filters['account_id'] = $account->id;
        }

        return $this->orderRepository->paginate($filters);
    }

    public function findById(int $id)
    {
        return $this->orderRepository->findById($id);
    }

    public function syncFromMeli(int $userId, array $params = [], ?int $selectedAccountId = null): array
    {
        $account = $this->accountRepository->findSelectedByUser($userId, $selectedAccountId);
        if (!$account) {
            return ['synced' => 0, 'errors' => ['No hay cuenta MeLi activa']];
        }

        $synced = 0;
        $errors = [];

        try {
            $meliOrders = $this->meliService->getOrders($account, $params);

            DB::transaction(function () use ($meliOrders, $account, &$synced, &$errors) {
                foreach ($meliOrders['results'] ?? [] as $meliOrder) {
                    try {
                        $customerId = $this->ensureCustomer($meliOrder, $account->id);
                        $dto        = OrderDTO::fromMeliResponse($meliOrder, $account->id, $customerId);
                        $existing   = $this->orderRepository->findByMeliOrderId($dto->meliOrderId);

                        if ($existing) {
                            $order = $this->orderRepository->update($existing, $dto->toArray());
                        } else {
                            $order = $this->orderRepository->create($dto->toArray());
                            $this->syncOrderItems($order, $dto->items);
                        }

                        $synced++;
                    } catch (\Throwable $e) {
                        Log::error('OrderService: error syncing order', [
                            'order_id' => $meliOrder['id'] ?? null,
                            'error'    => $e->getMessage(),
                        ]);
                        $errors[] = $e->getMessage();
                    }
                }
            });
        } catch (\Throwable $e) {
            Log::error('OrderService: MeLi API error', ['error' => $e->getMessage()]);
            $errors[] = $e->getMessage();
        }

        return compact('synced', 'errors');
    }

    public function findForAccount(MercadolibreAccount $account, int $id): ?Order
    {
        return $this->orderRepository->findForAccount($account->id, $id);
    }

    public function setFinalPrice(Order $order, float $finalPrice): Order
    {
        return $this->orderRepository->update($order, ['final_price' => $finalPrice]);
    }

    public function shippingLabel(MercadolibreAccount $account, Order $order): string
    {
        if (!$order->shipping_id) {
            throw new BusinessRuleException('La venta no tiene envío asociado.');
        }

        $response = $this->meliService->getShippingLabel($account, $order->shipping_id);

        if ($response->status() === 400) {
            throw new BusinessRuleException('La etiqueta no está disponible: el envío ya fue entregado o no está listo.');
        }

        if (!$response->successful()) {
            throw MeliApiException::fromResponse('descargar etiqueta', $response->json() ?? []);
        }

        return $response->body();
    }

    private function ensureCustomer(array $meliOrder, int $accountId): ?int
    {
        $buyer = $meliOrder['buyer'] ?? null;
        if (!$buyer) {
            return null;
        }

        $customer = Customer::firstOrCreate(
            ['meli_customer_id' => (string) $buyer['id']],
            [
                'mercadolibre_account_id' => $accountId,
                'name'                    => trim(($buyer['first_name'] ?? '') . ' ' . ($buyer['last_name'] ?? '')),
                'nickname'                => $buyer['nickname'] ?? '',
                'email'                   => $buyer['email'] ?? null,
            ]
        );

        return $customer->id;
    }

    private function syncOrderItems(\App\Models\Order $order, array $items): void
    {
        foreach ($items as $item) {
            $product = Product::where('meli_item_id', $item['meli_item_id'])->first();

            OrderItem::create([
                'order_id'    => $order->id,
                'product_id'  => $product?->id,
                'meli_item_id'=> $item['meli_item_id'],
                'title'       => $item['title'],
                'quantity'    => $item['quantity'],
                'unit_price'  => $item['unit_price'],
                'total_price' => $item['total_price'],
            ]);
        }
    }
}
