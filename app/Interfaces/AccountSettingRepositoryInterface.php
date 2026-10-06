<?php

namespace App\Interfaces;

use App\Enums\SettingKey;
use App\Models\AccountSetting;
use Illuminate\Database\Eloquent\Collection;

interface AccountSettingRepositoryInterface
{
    public function all(int $accountId): Collection;
    public function find(int $accountId, SettingKey $key): ?AccountSetting;
    public function put(int $accountId, SettingKey $key, mixed $value, ?bool $isEnabled = null, bool $touchValueDate = false): AccountSetting;
}
