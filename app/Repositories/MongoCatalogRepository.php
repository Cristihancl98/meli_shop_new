<?php

namespace App\Repositories;

use App\Interfaces\ExternalCatalogRepositoryInterface;
use MongoDB\BSON\Regex;
use MongoDB\Client;
use MongoDB\Collection;

class MongoCatalogRepository implements ExternalCatalogRepositoryInterface
{
    private const PRODUCTS_COLLECTION = 'mproductos';
    private const SETTINGS_COLLECTION = 'mconfiguraciones';
    private const MAX_RESULTS         = 200;
    private const TYPE_MAP            = ['root' => 'array', 'document' => 'array', 'array' => 'array'];
    private const INVISIBLE_PREFIX    = '[\\x{200E}\\x{200F}\\x{FEFF}\\s]*';

    private ?Client $client = null;

    public function findBySku(string $sku): ?array
    {
        $doc = $this->collection(self::PRODUCTS_COLLECTION)->findOne(
            ['sku' => new Regex('^' . self::INVISIBLE_PREFIX . preg_quote(trim($sku), '/') . '\\s*$', 'i')],
            ['sort' => ['fechaInserccion' => -1]]
        );

        return $doc ? $this->normalize($doc) : null;
    }

    public function findManyBySkus(array $skus): array
    {
        $patterns = array_map(
            fn (string $sku) => new Regex('^' . self::INVISIBLE_PREFIX . preg_quote(trim($sku), '/') . '\\s*$', 'i'),
            array_values(array_filter($skus, fn ($sku) => trim((string) $sku) !== ''))
        );

        if ($patterns === []) {
            return [];
        }

        $documents = [];
        $cursor    = $this->collection(self::PRODUCTS_COLLECTION)->find(
            ['sku' => ['$in' => $patterns]],
            ['sort' => ['fechaInserccion' => 1]]
        );

        foreach ($cursor as $doc) {
            $doc = $this->normalize($doc);
            $documents[mb_strtoupper($doc['sku'] ?? '')] = $doc;
        }

        return $documents;
    }

    public function findByCategory(string $meliCategoryId): array
    {
        return $this->findMany(['categoriaMeli' => $meliCategoryId]);
    }

    public function searchByTitle(string $title): array
    {
        return $this->findMany(['titulo' => new Regex(preg_quote($title, '/'), 'i')]);
    }

    public function currentDollarPrice(): ?float
    {
        $doc = $this->collection(self::SETTINGS_COLLECTION)->findOne(
            ['parametro' => 'precioDolar'],
            ['projection' => ['valor' => 1]]
        );

        $value = $doc['valor'] ?? null;

        return is_numeric($value) ? (float) $value : null;
    }

    public function iterate(string $mode, string $term = ''): iterable
    {
        $filter = match ($mode) {
            'title'    => ['titulo' => new Regex(preg_quote($term, '/'), 'i')],
            'category' => ['categoriaMeli' => $term],
            default    => [],
        };

        $cursor = $this->collection(self::PRODUCTS_COLLECTION)->find($filter, [
            'sort'      => ['fechaInserccion' => -1],
            'batchSize' => self::MAX_RESULTS,
        ]);

        foreach ($cursor as $doc) {
            yield $this->normalize($doc);
        }
    }

    private function findMany(array $filter): array
    {
        $cursor = $this->collection(self::PRODUCTS_COLLECTION)->find($filter, ['limit' => self::MAX_RESULTS]);

        return array_map(fn (array $doc) => $this->normalize($doc), $cursor->toArray());
    }

    private function collection(string $name): Collection
    {
        $this->client ??= new Client((string) config('database.connections.mongodb.dsn'));

        return $this->client->selectCollection(
            (string) config('database.connections.mongodb.database'),
            $name,
            ['typeMap' => self::TYPE_MAP]
        );
    }

    private function normalize(array $doc): array
    {
        if (!isset($doc['id']) && isset($doc['_id'])) {
            $doc['id'] = $doc['_id'];
        }

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
