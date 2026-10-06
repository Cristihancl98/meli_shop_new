<?php

namespace App\Repositories;

use App\Enums\SettingKey;
use App\Interfaces\StoreSettingRepositoryInterface;
use App\Models\StoreSetting;
use Illuminate\Database\Eloquent\Collection;

class StoreSettingRepository implements StoreSettingRepositoryInterface
{
    public function all(): Collection
    {
        return StoreSetting::all();
    }

    public function find(SettingKey $key): ?StoreSetting
    {
        return StoreSetting::where('key', $key->value)->first();
    }

    public function put(SettingKey $key, mixed $value, ?bool $isEnabled = null, bool $touchValueDate = false): StoreSetting
    {
        $attributes = ['value' => $key->isJson() ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value];

        if ($isEnabled !== null) {
            $attributes['is_enabled'] = $isEnabled;
        }

        if ($touchValueDate) {
            $attributes['value_updated_at'] = now();
        }

        return StoreSetting::updateOrCreate(['key' => $key->value], $attributes);
    }
}
