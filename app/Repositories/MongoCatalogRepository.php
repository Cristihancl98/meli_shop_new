<?php

namespace App\Repositories;

use App\Interfaces\ExternalCatalogRepositoryInterface;
use Illuminate\Support\Facades\DB;

class MongoCatalogRepository implements ExternalCatalogRepositoryInterface
{
    private const PRODUCTS_COLLECTION = 'mproductos';
    private const SETTINGS_COLLECTION = 'mconfiguraciones';
    private const MAX_RESULTS         = 200;

    public function findBySku(string $sku): ?array
    {
        $invisiblePrefix = '[\\x{200E}\\x{200F}\\x{FEFF}\\s]*';

        $doc = DB::connection('mongodb')
            ->table(self::PRODUCTS_COLLECTION)
            ->where('sku', 'regex', '/^' . $invisiblePrefix . preg_quote(trim($sku), '/') . '\\s*$/i')
            ->orderBy('fechaInserccion', 'desc')
            ->first();

        return $doc ? $this->normalize((array) $doc) : null;
    }

    public function findByCategory(string $meliCategoryId): array
    {
        return DB::connection('mongodb')
            ->table(self::PRODUCTS_COLLECTION)
            ->where('categoriaMeli', $meliCategoryId)
            ->limit(self::MAX_RESULTS)
            ->get()
            ->map(fn ($doc) => $this->normalize((array) $doc))
            ->all();
    }

    public function searchByTitle(string $title): array
    {
        return DB::connection('mongodb')
            ->table(self::PRODUCTS_COLLECTION)
            ->where('titulo', 'regex', '/' . preg_quote($title, '/') . '/i')
            ->limit(self::MAX_RESULTS)
            ->get()
            ->map(fn ($doc) => $this->normalize((array) $doc))
            ->all();
    }

    public function currentDollarPrice(): ?float
    {
        $value = DB::connection('mongodb')
            ->table(self::SETTINGS_COLLECTION)
            ->where('parametro', 'precioDolar')
            ->value('valor');

        return is_numeric($value) ? (float) $value : null;
    }

    private function normalize(array $doc): array
    {
        if (isset($doc['id']) && !is_scalar($doc['id'])) {
            $doc['id'] = (string) $doc['id'];
        }

        unset($doc['_id']);

        if (isset($doc['sku']) && is_string($doc['sku'])) {
            $doc['sku'] = trim(preg_replace('/\p{Cf}+/u', '', $doc['sku']));
        }

        $doc['atributos'] = $this->toArray($doc['atributos'] ?? []);
        unset($doc['atributos']['_id'], $doc['atributos']['id']);

        return $doc;
    }

    private function toArray(mixed $value): array
    {
        return json_decode(json_encode($value), true) ?? [];
    }
}
