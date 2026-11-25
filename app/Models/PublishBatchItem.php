<?php

namespace App\Models;

use App\Enums\BatchItemStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PublishBatchItem extends Model
{
    protected $fillable = ['publish_batch_id', 'sku', 'status', 'data', 'error', 'product_id', 'meli_item_id', 'attempts'];

    protected $casts = [
        'status'   => BatchItemStatus::class,
        'data'     => 'array',
        'attempts' => 'integer',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(PublishBatch::class, 'publish_batch_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function title(): ?string
    {
        $data  = $this->data ?? [];
        $title = ($data['titulo_meli'] ?? '') ?: ($data['titulo'] ?? '');

        return $title !== '' ? mb_substr((string) $title, 0, 60) : null;
    }

    public function thumbnail(): ?string
    {
        return $this->data['imagenes'][0] ?? null;
    }

    public function meliUrl(): ?string
    {
        if ($this->product?->meli_url) {
            return $this->product->meli_url;
        }

        return $this->meli_item_id
            ? 'https://articulo.mercadolibre.com.co/' . preg_replace('/^([A-Z]{3})-?(\d+)$/', '$1-$2', $this->meli_item_id)
            : null;
    }
}
