<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StoreMeliAccount extends Model
{
    protected $connection = 'landlord';

    protected $fillable = ['store_id', 'meli_user_id'];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}
