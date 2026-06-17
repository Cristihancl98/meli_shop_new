<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MercadolibreAccount extends Model
{
    protected $fillable = [
        'user_id',
        'meli_user_id',
        'access_token',
        'refresh_token',
        'expires_at',
        'nickname',
        'email',
        'is_active',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'is_active'  => 'boolean',
    ];

    protected $hidden = ['access_token', 'refresh_token'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    public function syncLogs(): HasMany
    {
        return $this->hasMany(SyncLog::class);
    }

    public function salesStatistics(): HasMany
    {
        return $this->hasMany(SalesStatistic::class);
    }

    public function isTokenExpired(): bool
    {
        return $this->expires_at->subMinutes(5)->isPast();
    }
}
