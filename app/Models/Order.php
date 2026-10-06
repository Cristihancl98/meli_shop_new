<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'meli_order_id',
        'pack_id',
        'mercadolibre_account_id',
        'customer_id',
        'status',
        'payment_status',
        'shipping_status',
        'shipping_id',
        'total_amount',
        'final_price',
        'order_date',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'final_price'  => 'decimal:2',
        'order_date'   => 'datetime',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(MercadolibreAccount::class, 'mercadolibre_account_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function postSaleMessages(): HasMany
    {
        return $this->hasMany(PostSaleMessage::class);
    }

    public function conversationId(): string
    {
        return $this->pack_id ?: $this->meli_order_id;
    }
}
