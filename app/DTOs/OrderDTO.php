<?php

namespace App\DTOs;

class OrderDTO
{
    public function __construct(
        public readonly string  $meliOrderId,
        public readonly int     $mercadolibreAccountId,
        public readonly ?int    $customerId,
        public readonly string  $status,
        public readonly string  $paymentStatus,
        public readonly string  $shippingStatus,
        public readonly float   $totalAmount,
        public readonly string  $orderDate,
        public readonly array   $items = [],
    ) {}

    public static function fromMeliResponse(array $data, int $accountId, ?int $customerId = null): self
    {
        $orderItems = [];
        foreach ($data['order_items'] ?? [] as $item) {
            $orderItems[] = [
                'meli_item_id'  => $item['item']['id'] ?? null,
                'title'         => $item['item']['title'] ?? '',
                'quantity'      => $item['quantity'] ?? 1,
                'unit_price'    => $item['unit_price'] ?? 0,
                'total_price'   => ($item['quantity'] ?? 1) * ($item['unit_price'] ?? 0),
            ];
        }

        return new self(
            meliOrderId:             (string) $data['id'],
            mercadolibreAccountId:   $accountId,
            customerId:              $customerId,
            status:                  $data['status'] ?? 'pending',
            paymentStatus:           $data['payments'][0]['status'] ?? 'pending',
            shippingStatus:          $data['shipping']['status'] ?? 'pending',
            totalAmount:             (float) ($data['total_amount'] ?? 0),
            orderDate:               $data['date_created'] ?? now()->toIso8601String(),
            items:                   $orderItems,
        );
    }

    public function toArray(): array
    {
        return [
            'meli_order_id'             => $this->meliOrderId,
            'mercadolibre_account_id'   => $this->mercadolibreAccountId,
            'customer_id'               => $this->customerId,
            'status'                    => $this->status,
            'payment_status'            => $this->paymentStatus,
            'shipping_status'           => $this->shippingStatus,
            'total_amount'              => $this->totalAmount,
            'order_date'                => $this->orderDate,
        ];
    }
}
