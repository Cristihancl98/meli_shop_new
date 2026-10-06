<?php

namespace App\Models;

use App\Enums\SettingKey;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'mercadolibre_account_id',
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

    public function account(): BelongsTo
    {
        return $this->belongsTo(MercadolibreAccount::class, 'mercadolibre_account_id');
    }

    public function typedValue(): mixed
    {
        return $this->key->cast($this->value);
    }
}
