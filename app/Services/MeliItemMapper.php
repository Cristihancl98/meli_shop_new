<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use Carbon\Carbon;

class MeliItemMapper
{
    private const KEEP_LOCAL_WHEN_MISSING = ['sku', 'category_id', 'meli_category_id', 'published_at', 'thumbnail', 'permalink', 'listing_type_id'];

    public static function normalizeItemId(string $itemId): string
    {
        return strtoupper(trim($itemId));
    }

    public function toUpdateAttributes(array $item): array
    {
        $attributes = $this->toProductAttributes($item);

        foreach (self::KEEP_LOCAL_WHEN_MISSING as $key) {
            if ($attributes[$key] === null || $attributes[$key] === '') {
                unset($attributes[$key]);
            }
        }

        return $attributes;
    }

    public function toProductAttributes(array $item): array
    {
        $categoryId = !empty($item['category_id'])
            ? Category::where('meli_category_id', $item['category_id'])->value('id')
            : null;

        return [
            'meli_item_id'     => self::normalizeItemId($item['id']),
            'meli_category_id' => $item['category_id'] ?? null,
            'category_id'      => $categoryId,
            'published_at'     => isset($item['date_created']) ? Carbon::parse($item['date_created']) : null,
            'sku'              => collect($item['attributes'] ?? [])->firstWhere('id', 'SELLER_SKU')['value_name'] ?? null,
            'meli_attributes'  => $item['attributes'] ?? null,
            'pictures'         => array_values(array_filter(array_column($item['pictures'] ?? [], 'secure_url'))),
            'variations'       => $item['variations'] ?? null,
            'sold_quantity'    => $item['sold_quantity'] ?? 0,
            'title'            => $item['title'] ?? '',
            'price'            => $item['price'] ?? 0,
            'stock'            => $item['available_quantity'] ?? 0,
            'status'           => Product::normalizeMeliStatus($item['status'] ?? null),
            'condition'        => $item['condition'] ?? 'new',
            'listing_type_id'  => $item['listing_type_id'] ?? null,
            'thumbnail'        => $item['secure_thumbnail'] ?? $item['thumbnail'] ?? null,
            'permalink'        => $item['permalink'] ?? null,
            'last_sync'        => now(),
        ];
    }
}
