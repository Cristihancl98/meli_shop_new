<?php

namespace App\Models;

use App\Observers\MercadolibreAccountObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[ObservedBy(MercadolibreAccountObserver::class)]
class MercadolibreAccount extends Model
{
    use HasFactory;

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

    public function settings(): HasMany
    {
        return $this->hasMany(AccountSetting::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class);
    }

    public function isTokenExpired(): bool
    {
        return $this->expires_at->subMinutes(5)->isPast();
    }
}
