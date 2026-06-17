<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductStatistic extends Model
{
    protected $fillable = [
        'product_id',
        'quantity_sold',
        'total_revenue',
        'last_sale_date',
    ];

    protected $casts = [
        'total_revenue'  => 'decimal:2',
        'last_sale_date' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
