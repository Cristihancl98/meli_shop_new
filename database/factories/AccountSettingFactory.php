<?php

namespace Database\Factories;

use App\Enums\SettingKey;
use App\Models\AccountSetting;
use App\Models\MercadolibreAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

class AccountSettingFactory extends Factory
{
    protected $model = AccountSetting::class;

    public function definition(): array
    {
        return [
            'mercadolibre_account_id' => MercadolibreAccount::factory(),
            'key'                     => SettingKey::Iva,
            'value'                   => '19',
            'is_enabled'              => true,
        ];
    }
}
