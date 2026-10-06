<?php

namespace App\Enums;

enum SettingKey: string
{
    case ShippingPrice       = 'shipping_price';
    case ProfitRanges        = 'profit_ranges';
    case DollarPrice         = 'dollar_price';
    case MeliCommission      = 'meli_commission';
    case Iva                 = 'iva';
    case LogisticsPrice      = 'logistics_price';
    case ManufacturingDays   = 'manufacturing_days';
    case AmazonCommission    = 'amazon_commission';
    case WeightRanges        = 'weight_ranges';
    case NationalTaxRanges   = 'national_tax_ranges';
    case SaleMessage         = 'sale_message';
    case DescriptionTemplate = 'description_template';
    case ListingType         = 'listing_type';
    case WarrantyDays        = 'warranty_days';
    case DefaultQuantity     = 'default_quantity';
    case ScrapingUrl         = 'scraping_url';
    case Reputation          = 'reputation';
    case CurrentDollarPrice  = 'current_dollar_price';
    case OrdersSyncedAt      = 'orders_synced_at';

    public function defaultValue(): mixed
    {
        return config("store_defaults.{$this->value}", match ($this) {
            self::ProfitRanges, self::WeightRanges, self::NationalTaxRanges => [],
            self::Reputation, self::OrdersSyncedAt => null,
            default => 0,
        });
    }

    public function isAccountScoped(): bool
    {
        return in_array($this, [self::Reputation, self::OrdersSyncedAt], true);
    }

    public function isSystemManaged(): bool
    {
        return in_array($this, [self::Reputation, self::CurrentDollarPrice, self::OrdersSyncedAt], true);
    }

    public function isJson(): bool
    {
        return in_array($this, [self::ProfitRanges, self::WeightRanges, self::NationalTaxRanges], true);
    }

    public function cast(?string $raw): mixed
    {
        if ($raw === null) {
            return $this->defaultValue();
        }

        return match (true) {
            $this->isJson()    => json_decode($raw, true) ?? [],
            $this->isInteger() => (int) $raw,
            $this->isDecimal() => (float) $raw,
            default            => $raw,
        };
    }

    private function isInteger(): bool
    {
        return in_array($this, [self::ManufacturingDays, self::WarrantyDays, self::DefaultQuantity], true);
    }

    private function isDecimal(): bool
    {
        return in_array($this, [
            self::ShippingPrice, self::DollarPrice, self::MeliCommission, self::Iva,
            self::LogisticsPrice, self::AmazonCommission, self::CurrentDollarPrice,
        ], true);
    }

    public static function pricingKeys(): array
    {
        return [
            self::ShippingPrice, self::ProfitRanges, self::DollarPrice, self::MeliCommission,
            self::Iva, self::LogisticsPrice, self::ManufacturingDays, self::AmazonCommission,
            self::WeightRanges, self::NationalTaxRanges, self::SaleMessage, self::DescriptionTemplate,
            self::ListingType, self::WarrantyDays, self::DefaultQuantity, self::ScrapingUrl,
        ];
    }
}
