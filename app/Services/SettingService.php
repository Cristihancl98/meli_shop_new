<?php

namespace App\Services;

use App\Enums\SettingKey;
use App\Interfaces\AccountSettingRepositoryInterface;
use App\Interfaces\StoreSettingRepositoryInterface;
use App\Models\MercadolibreAccount;

class SettingService
{
    public function __construct(
        private readonly StoreSettingRepositoryInterface $storeSettingRepository,
        private readonly AccountSettingRepositoryInterface $accountSettingRepository
    ) {}

    public function get(SettingKey $key, ?MercadolibreAccount $account = null): mixed
    {
        $setting = $key->isAccountScoped()
            ? ($account ? $this->accountSettingRepository->find($account->id, $key) : null)
            : $this->storeSettingRepository->find($key);

        return $setting ? $setting->typedValue() : $key->defaultValue();
    }

    public function isEnabled(SettingKey $key): bool
    {
        return $this->storeSettingRepository->find($key)?->is_enabled ?? false;
    }

    public function pricingSettings(): array
    {
        $stored   = $this->storeSettingRepository->all()->keyBy(fn ($setting) => $setting->key->value);
        $settings = [];

        foreach (SettingKey::pricingKeys() as $key) {
            $settings[$key->value] = isset($stored[$key->value])
                ? $stored[$key->value]->typedValue()
                : $key->defaultValue();
        }

        $settings['sale_message_enabled'] = $stored[SettingKey::SaleMessage->value]->is_enabled ?? false;

        return $settings;
    }

    public function update(array $values): array
    {
        $saleMessageEnabled = $values['sale_message_enabled'] ?? null;
        unset($values['sale_message_enabled']);

        foreach ($values as $key => $value) {
            $settingKey = SettingKey::from($key);
            $isEnabled  = $settingKey === SettingKey::SaleMessage && $saleMessageEnabled !== null
                ? (bool) $saleMessageEnabled
                : null;

            $this->storeSettingRepository->put($settingKey, $value, $isEnabled, true);
        }

        if ($saleMessageEnabled !== null && !array_key_exists(SettingKey::SaleMessage->value, $values)) {
            $this->storeSettingRepository->put(
                SettingKey::SaleMessage,
                $this->get(SettingKey::SaleMessage),
                (bool) $saleMessageEnabled
            );
        }

        return $this->pricingSettings();
    }

    public function initializeDefaults(): int
    {
        $stored  = $this->storeSettingRepository->all()->map(fn ($setting) => $setting->key->value)->all();
        $created = 0;

        foreach (SettingKey::cases() as $key) {
            if ($key->isSystemManaged() || $key->isAccountScoped() || in_array($key->value, $stored, true)) {
                continue;
            }

            $this->storeSettingRepository->put($key, $key->defaultValue(), $key !== SettingKey::SaleMessage);
            $created++;
        }

        return $created;
    }

    public function recordSystemValue(SettingKey $key, mixed $value, ?MercadolibreAccount $account = null): void
    {
        $key->isAccountScoped()
            ? $this->accountSettingRepository->put($account->id, $key, $value, null, true)
            : $this->storeSettingRepository->put($key, $value, null, true);
    }

    public function reputation(?MercadolibreAccount $account): array
    {
        $setting = $account ? $this->accountSettingRepository->find($account->id, SettingKey::Reputation) : null;

        return [
            'reputation' => $setting?->value,
            'updated_at' => $setting?->value_updated_at?->toDateTimeString(),
        ];
    }

    public function dollarComparison(): array
    {
        return [
            'store_dollar'   => (float) $this->get(SettingKey::DollarPrice),
            'current_dollar' => (float) $this->get(SettingKey::CurrentDollarPrice),
        ];
    }
}
