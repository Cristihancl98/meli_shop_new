<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PublishBatch extends Model
{
    protected $fillable = ['mercadolibre_account_id', 'user_id'];

    public function account(): BelongsTo
    {
        return $this->belongsTo(MercadolibreAccount::class, 'mercadolibre_account_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PublishBatchItem::class)->orderBy('id');
    }
}
