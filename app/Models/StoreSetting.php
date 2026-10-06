<?php

namespace App\Models;

use App\Enums\SettingKey;
use Illuminate\Database\Eloquent\Model;

class StoreSetting extends Model
{
    protected $table = 'settings';

    protected $fillable = [
        'key',
        'value',
        'is_enabled',
        'value_updated_at',
    ];

    protected $casts = [
        'key'              => SettingKey::class,
        'is_enabled'       => 'boolean',
        'value_updated_at' => 'datetime',
    ];

    public function typedValue(): mixed
    {
        return $this->key->cast($this->value);
    }
}
