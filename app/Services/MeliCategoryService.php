<?php

namespace App\Services;

use App\DTOs\MeliCategoryLevelDTO;
use App\Exceptions\BusinessRuleException;
use App\Models\MercadolibreAccount;
use Illuminate\Support\Facades\Cache;

class MeliCategoryService
{
    private const CACHE_HOURS = 24;

    public function __construct(private readonly MercadoLibreService $meliService) {}

    public function level(MercadolibreAccount $account, ?string $categoryId): MeliCategoryLevelDTO
    {
        return $categoryId ? $this->category($account, $categoryId) : $this->root($account);
    }

    private function root(MercadolibreAccount $account): MeliCategoryLevelDTO
    {
        $categories = $this->remember('root', fn () => $this->meliService->getSiteCategories($account));

        if (!array_is_list($categories) || $categories === []) {
            throw new BusinessRuleException('Mercado Libre no devolvió las categorías: ' . rtrim($categories['message'] ?? 'respuesta vacía', '.') . '.');
        }

        return new MeliCategoryLevelDTO(null, null, [], $this->simplify($categories));
    }

    private function category(MercadolibreAccount $account, string $categoryId): MeliCategoryLevelDTO
    {
        $category = $this->remember($categoryId, fn () => $this->meliService->getCategory($account, $categoryId));

        if (!isset($category['id'])) {
            throw new BusinessRuleException("La categoría {$categoryId} no existe en Mercado Libre.");
        }

        return new MeliCategoryLevelDTO(
            $category['id'],
            $category['name'] ?? $category['id'],
            $this->simplify($category['path_from_root'] ?? []),
            $this->simplify($category['children_categories'] ?? []),
        );
    }

    private function remember(string $key, callable $fetch): array
    {
        $cacheKey = 'meli:categories:' . config('services.mercadolibre.country_code') . ':' . $key;

        if ($cached = Cache::get($cacheKey)) {
            return $cached;
        }

        $data = $fetch();

        if (isset($data['id']) || array_is_list($data) && $data !== []) {
            Cache::put($cacheKey, $data, now()->addHours(self::CACHE_HOURS));
        }

        return $data;
    }

    private function simplify(array $categories): array
    {
        return array_values(array_map(
            fn (array $category) => ['id' => (string) $category['id'], 'name' => (string) ($category['name'] ?? $category['id'])],
            array_filter($categories, fn ($category) => is_array($category) && isset($category['id']))
        ));
    }
}
