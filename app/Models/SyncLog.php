<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SyncLog extends Model
{
    protected $fillable = [
        'mercadolibre_account_id',
        'type',
        'status',
        'message',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'started_at'  => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(MercadolibreAccount::class, 'mercadolibre_account_id');
    }
}
