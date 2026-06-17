<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'meli_item_id',
        'mercadolibre_account_id',
        'category_id',
        'title',
        'description',
        'price',
        'stock',
        'status',
        'condition',
        'listing_type_id',
        'thumbnail',
        'permalink',
        'last_sync',
    ];

    protected $casts = [
        'price'     => 'decimal:2',
        'last_sync' => 'datetime',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(MercadolibreAccount::class, 'mercadolibre_account_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function statistics(): HasOne
    {
        return $this->hasOne(ProductStatistic::class);
    }
}
