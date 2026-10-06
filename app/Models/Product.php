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

    public const STATUSES = ['active', 'paused', 'closed', 'under_review', 'inactive'];

    protected $fillable = [
        'meli_item_id',
        'sku',
        'mercadolibre_account_id',
        'category_id',
        'meli_category_id',
        'published_by',
        'title',
        'description',
        'description_synced',
        'price',
        'base_price',
        'weight',
        'dimensions',
        'meli_attributes',
        'pictures',
        'variations',
        'stock',
        'sold_quantity',
        'status',
        'condition',
        'listing_type_id',
        'thumbnail',
        'permalink',
        'last_sync',
        'published_at',
    ];

    protected $casts = [
        'price'      => 'decimal:2',
        'base_price' => 'decimal:2',
        'weight'     => 'decimal:2',
        'dimensions' => 'array',
        'meli_attributes' => 'array',
        'pictures'   => 'array',
        'variations' => 'array',
        'last_sync'  => 'datetime',
        'published_at' => 'datetime',
        'description_synced' => 'boolean',
    ];

    public static function normalizeMeliStatus(?string $status): string
    {
        return in_array($status, self::STATUSES, true) ? $status : 'closed';
    }

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

    public function publisher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class);
    }

    public function statistics(): HasOne
    {
        return $this->hasOne(ProductStatistic::class);
    }
}
