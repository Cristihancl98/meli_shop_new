<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MeliNotification extends Model
{
    use HasFactory;

    public const TYPE_SALE              = 'sale';
    public const TYPE_PRE_SALE_QUESTION = 'pre_sale_question';
    public const TYPE_POST_SALE_MESSAGE = 'post_sale_message';

    protected $fillable = [
        'mercadolibre_account_id',
        'type',
        'resource',
        'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(MercadolibreAccount::class, 'mercadolibre_account_id');
    }
}
