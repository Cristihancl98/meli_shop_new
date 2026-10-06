<?php

namespace App\Services;

use App\Events\OrderSynced;
use App\Exceptions\MeliApiException;
use App\Interfaces\OrderRepositoryInterface;
use App\Interfaces\ProductRepositoryInterface;
use App\Models\Customer;
use App\Models\MercadolibreAccount;
use App\Models\Order;
use App\Models\OrderItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class OrderImportService
{
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly ProductRepositoryInterface $productRepository,
        private readonly MercadoLibreService $meliService
    ) {}

    public function importById(MercadolibreAccount $account, string $meliOrderId): Order
    {
        $meliOrder = $this->meliService->getOrder($account, $meliOrderId);

        if (!isset($meliOrder['id'])) {
            throw MeliApiException::fromResponse('consultar venta', $meliOrder ?? []);
        }

        return $this->import($account, $meliOrder);
    }

    public function import(MercadolibreAccount $account, array $meliOrder): Order
    {
        $order = DB::transaction(function () use ($account, $meliOrder) {
            $customer = $this->upsertCustomer($account, $meliOrder['buyer'] ?? []);

            $order = Order::updateOrCreate(
                ['meli_order_id' => (string) $meliOrder['id']],
                [
                    'mercadolibre_account_id' => $account->id,
                    'customer_id'             => $customer->id,
                    'pack_id'                 => isset($meliOrder['pack_id']) ? (string) $meliOrder['pack_id'] : null,
                    'status'                  => $meliOrder['status'] ?? 'pending',
                    'payment_status'          => $meliOrder['payments'][0]['status'] ?? null,
                    'shipping_status'         => $meliOrder['shipping']['status'] ?? null,
                    'shipping_id'             => isset($meliOrder['shipping']['id']) ? (string) $meliOrder['shipping']['id'] : null,
                    'total_amount'            => (float) ($meliOrder['total_amount'] ?? 0),
                    'order_date'              => isset($meliOrder['date_created']) ? Carbon::parse($meliOrder['date_created']) : now(),
                ]
            );

            if ($order->wasRecentlyCreated) {
                $this->createItems($account, $order, $meliOrder['order_items'] ?? []);
            }

            return $order;
        });

        OrderSynced::dispatch($order->fresh(['items']));

        return $order;
    }

    private function upsertCustomer(MercadolibreAccount $account, array $buyer): Customer
    {
        $name = trim(($buyer['first_name'] ?? '') . ' ' . ($buyer['last_name'] ?? ''));

        return Customer::firstOrCreate(
            ['meli_customer_id' => (string) ($buyer['id'] ?? 'unknown')],
            [
                'mercadolibre_account_id' => $account->id,
                'name'                    => $name ?: ($buyer['nickname'] ?? 'Sin nombre'),
                'nickname'                => $buyer['nickname'] ?? null,
                'email'                   => $buyer['email'] ?? null,
            ]
        );
    }

    private function createItems(MercadolibreAccount $account, Order $order, array $items): void
    {
        $thumbnails = $this->fetchThumbnails($account, array_filter(array_column(array_column($items, 'item'), 'id')));

        foreach ($items as $item) {
            $meliItemId = $item['item']['id'] ?? null;
            $quantity   = (int) ($item['quantity'] ?? 1);
            $unitPrice  = (float) ($item['unit_price'] ?? 0);

            OrderItem::create([
                'order_id'     => $order->id,
                'product_id'   => $meliItemId ? $this->productRepository->findByMeliItemId($meliItemId)?->id : null,
                'meli_item_id' => $meliItemId,
                'sku'          => $item['item']['seller_sku'] ?? null,
                'title'        => $item['item']['title'] ?? '',
                'thumbnail'    => $thumbnails[$meliItemId] ?? null,
                'quantity'     => $quantity,
                'unit_price'   => $unitPrice,
                'total_price'  => $quantity * $unitPrice,
            ]);
        }
    }

    private function fetchThumbnails(MercadolibreAccount $account, array $itemIds): array
    {
        $thumbnails = [];

        foreach (array_chunk(array_values(array_unique($itemIds)), 20) as $chunk) {
            foreach ($this->meliService->getItems($account, $chunk) as $entry) {
                $body = $entry['body'] ?? [];
                if (isset($body['id'])) {
                    $thumbnails[$body['id']] = $body['pictures'][0]['secure_url'] ?? $body['thumbnail'] ?? null;
                }
            }
        }

        return $thumbnails;
    }
}
