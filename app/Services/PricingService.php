<?php

namespace App\Services;

use App\DTOs\PriceBreakdownDTO;

class PricingService
{
    private const ROUNDING_SURCHARGE = 2000;

    public function __construct(
        private readonly SettingService $settingService
    ) {}

    public function calculate(float $basePrice, float $weight): PriceBreakdownDTO
    {
        $settings = $this->settingService->pricingSettings();

        if ($basePrice <= 0) {
            return new PriceBreakdownDTO($basePrice, $weight, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, (float) $settings['dollar_price'], 0);
        }

        $price = $basePrice;

        $amazonCommission = $price * ((float) $settings['amazon_commission'] / 100);
        $price += $amazonCommission;

        $iva = $price * ((float) $settings['iva'] / 100);
        $price += $iva;

        $logisticsPrice = (float) $settings['logistics_price'];
        $shippingPrice  = (float) $settings['shipping_price'];
        $price += $logisticsPrice + $shippingPrice;

        $nationalTax = (float) ($this->findRange($settings['national_tax_ranges'], $weight)['price'] ?? 0);
        $price += $nationalTax;

        $usaShipping = (float) ($this->findRange($settings['weight_ranges'], $weight)['price'] ?? 0) * $weight;
        $price += $usaShipping;

        $profitPercentage = (float) ($this->findRange($settings['profit_ranges'], $basePrice)['percentage'] ?? 0);
        $profit = $price * ($profitPercentage / 100);
        $price += $profit;

        $meliCommission = $price * ((float) $settings['meli_commission'] / 100);
        $price += $meliCommission;

        $dollarPrice = (float) $settings['dollar_price'];
        $finalPrice  = round($price * $dollarPrice, -3) + self::ROUNDING_SURCHARGE;

        return new PriceBreakdownDTO(
            basePrice:        $basePrice,
            weight:           $weight,
            amazonCommission: $amazonCommission,
            iva:              $iva,
            logisticsPrice:   $logisticsPrice,
            shippingPrice:    $shippingPrice,
            nationalTax:      $nationalTax,
            usaShipping:      $usaShipping,
            profitPercentage: $profitPercentage,
            profit:           $profit,
            meliCommission:   $meliCommission,
            subtotalUsd:      $price,
            dollarPrice:      $dollarPrice,
            finalPrice:       $finalPrice,
        );
    }

    private function findRange(array $ranges, float $value): ?array
    {
        foreach ($ranges as $range) {
            if ($value >= (float) $range['from'] && $value <= (float) $range['to']) {
                return $range;
            }
        }

        return null;
    }
}
