<?php

namespace App\Repositories;

use App\Enums\SettingKey;
use App\Interfaces\AccountSettingRepositoryInterface;
use App\Models\AccountSetting;
use Illuminate\Database\Eloquent\Collection;

class AccountSettingRepository implements AccountSettingRepositoryInterface
{
    public function all(int $accountId): Collection
    {
        return AccountSetting::where('mercadolibre_account_id', $accountId)->get();
    }

    public function find(int $accountId, SettingKey $key): ?AccountSetting
    {
        return AccountSetting::where('mercadolibre_account_id', $accountId)
            ->where('key', $key->value)
            ->first();
    }

    public function put(int $accountId, SettingKey $key, mixed $value, ?bool $isEnabled = null, bool $touchValueDate = false): AccountSetting
    {
        $attributes = ['value' => $key->isJson() ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value];

        if ($isEnabled !== null) {
            $attributes['is_enabled'] = $isEnabled;
        }

        if ($touchValueDate) {
            $attributes['value_updated_at'] = now();
        }

        return AccountSetting::updateOrCreate(
            ['mercadolibre_account_id' => $accountId, 'key' => $key->value],
            $attributes
        );
    }
}
