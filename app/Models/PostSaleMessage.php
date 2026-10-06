<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostSaleMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'mercadolibre_account_id',
        'order_id',
        'pack_id',
        'meli_message_id',
        'text',
        'from_seller',
        'status',
        'sent_at',
        'seen',
        'sent_by',
    ];

    protected $casts = [
        'from_seller' => 'boolean',
        'seen'        => 'boolean',
        'sent_at'     => 'datetime',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(MercadolibreAccount::class, 'mercadolibre_account_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by');
    }
}
