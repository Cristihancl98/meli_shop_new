<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesStatistic extends Model
{
    protected $fillable = [
        'mercadolibre_account_id',
        'stat_date',
        'total_sales',
        'total_orders',
        'average_ticket',
    ];

    protected $casts = [
        'stat_date'     => 'date',
        'total_sales'   => 'decimal:2',
        'average_ticket'=> 'decimal:2',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(MercadolibreAccount::class, 'mercadolibre_account_id');
    }
}
