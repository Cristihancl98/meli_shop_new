<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Question extends Model
{
    use HasFactory;

    protected $fillable = [
        'mercadolibre_account_id',
        'meli_question_id',
        'meli_item_id',
        'product_id',
        'question',
        'question_status',
        'asked_at',
        'answer',
        'answer_status',
        'answered_at',
        'buyer_meli_id',
        'buyer_nickname',
        'seen',
        'handled_by',
    ];

    protected $casts = [
        'asked_at'    => 'datetime',
        'answered_at' => 'datetime',
        'seen'        => 'boolean',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(MercadolibreAccount::class, 'mercadolibre_account_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    public function isAnswered(): bool
    {
        return $this->answer !== null;
    }
}
