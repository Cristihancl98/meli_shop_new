<?php

namespace App\Enums;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;

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
    case MeliClientId        = 'meli_client_id';
    case MeliClientSecret    = 'meli_client_secret';
    case MeliRedirectUri     = 'meli_redirect_uri';

    public function defaultValue(): mixed
    {
        return config("store_defaults.{$this->value}", match ($this) {
            self::ProfitRanges, self::WeightRanges, self::NationalTaxRanges => [],
            self::Reputation, self::OrdersSyncedAt => null,
            self::MeliClientId     => (string) config('services.mercadolibre.client_id'),
            self::MeliClientSecret => (string) config('services.mercadolibre.client_secret'),
            self::MeliRedirectUri  => (string) config('services.mercadolibre.redirect_uri'),
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

    public function isMeliCredential(): bool
    {
        return in_array($this, self::meliCredentialKeys(), true);
    }

    public function isEncrypted(): bool
    {
        return $this === self::MeliClientSecret;
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
            $this->isEncrypted() => $this->decrypt($raw),
            $this->isJson()      => json_decode($raw, true) ?? [],
            $this->isInteger() => (int) $raw,
            $this->isDecimal() => (float) $raw,
            default            => $raw,
        };
    }

    public function serialize(mixed $value): mixed
    {
        return match (true) {
            $this->isJson()                                          => json_encode($value, JSON_UNESCAPED_UNICODE),
            $this->isEncrypted() && $value !== null && $value !== '' => Crypt::encryptString((string) $value),
            default                                                  => $value,
        };
    }

    private function decrypt(string $raw): string
    {
        try {
            return Crypt::decryptString($raw);
        } catch (DecryptException) {
            return '';
        }
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

    public static function meliCredentialKeys(): array
    {
        return [self::MeliClientId, self::MeliClientSecret, self::MeliRedirectUri];
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
