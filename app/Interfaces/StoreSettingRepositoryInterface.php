<?php

namespace App\Interfaces;

use App\Enums\SettingKey;
use App\Models\StoreSetting;
use Illuminate\Database\Eloquent\Collection;

interface StoreSettingRepositoryInterface
{
    public function all(): Collection;
    public function find(SettingKey $key): ?StoreSetting;
    public function put(SettingKey $key, mixed $value, ?bool $isEnabled = null, bool $touchValueDate = false): StoreSetting;
}
