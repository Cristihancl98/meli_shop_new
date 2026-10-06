<?php

namespace App\DTOs;

final class PriceBreakdownDTO
{
    public function __construct(
        public readonly float $basePrice,
        public readonly float $weight,
        public readonly float $amazonCommission,
        public readonly float $iva,
        public readonly float $logisticsPrice,
        public readonly float $shippingPrice,
        public readonly float $nationalTax,
        public readonly float $usaShipping,
        public readonly float $profitPercentage,
        public readonly float $profit,
        public readonly float $meliCommission,
        public readonly float $subtotalUsd,
        public readonly float $dollarPrice,
        public readonly float $finalPrice,
    ) {}

    public function toArray(): array
    {
        return [
            'base_price'        => $this->basePrice,
            'weight'            => $this->weight,
            'amazon_commission' => round($this->amazonCommission, 2),
            'iva'               => round($this->iva, 2),
            'logistics_price'   => round($this->logisticsPrice, 2),
            'shipping_price'    => round($this->shippingPrice, 2),
            'national_tax'      => round($this->nationalTax, 2),
            'usa_shipping'      => round($this->usaShipping, 2),
            'profit_percentage' => $this->profitPercentage,
            'profit'            => round($this->profit, 2),
            'meli_commission'   => round($this->meliCommission, 2),
            'subtotal_usd'      => round($this->subtotalUsd, 2),
            'dollar_price'      => $this->dollarPrice,
            'final_price'       => $this->finalPrice,
        ];
    }
}
