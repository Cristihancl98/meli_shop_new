<?php

namespace App\Services;

use App\Enums\SettingKey;
use App\Exceptions\BusinessRuleException;
use App\Interfaces\ExternalCatalogRepositoryInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CatalogService
{
    public const SOURCE_CATALOG = 'catalog';
    public const SOURCE_SCRAPER = 'scraper';

    private const SCRAPING_PATH = 'api/v1/app/scriping/';

    public function __construct(
        private readonly ExternalCatalogRepositoryInterface $catalogRepository,
        private readonly SettingService $settingService
    ) {}

    public function findProductBySku(string $sku): ?array
    {
        $document = $this->findInCatalog($sku);

        if ($document) {
            return $this->toLookup($document, self::SOURCE_CATALOG);
        }

        $scraped = $this->settingService->get(SettingKey::ScrapingUrl) ? $this->scrapeSku($sku) : null;

        return $scraped ? $this->toLookup($scraped, self::SOURCE_SCRAPER) : null;
    }

    public function findByCategory(string $meliCategoryId): array
    {
        return $this->catalogRepository->findByCategory($meliCategoryId);
    }

    public function searchByTitle(string $title): array
    {
        return $this->catalogRepository->searchByTitle($title);
    }

    public function currentDollarPrice(): ?float
    {
        return $this->catalogRepository->currentDollarPrice();
    }

    public function scrapeSku(string $sku): ?array
    {
        $baseUrl = $this->settingService->get(SettingKey::ScrapingUrl);

        if (!$baseUrl) {
            throw new BusinessRuleException('No hay URL de scraping configurada para esta tienda.');
        }

        try {
            $response = Http::timeout(60)->get(rtrim($baseUrl, '/') . '/' . self::SCRAPING_PATH . urlencode($sku));
        } catch (\Throwable $e) {
            Log::warning('Scraping SKU falló', ['sku' => $sku, 'error' => $e->getMessage()]);
            return null;
        }

        $data = $response->json();

        return isset($data['titulo']) ? $data : null;
    }

    private function findInCatalog(string $sku): ?array
    {
        try {
            return $this->catalogRepository->findBySku($sku);
        } catch (\Throwable $e) {
            report($e);

            if (!$this->settingService->get(SettingKey::ScrapingUrl)) {
                throw new BusinessRuleException('No fue posible consultar el catálogo de productos. Verifica la conexión con MongoDB.');
            }

            return null;
        }
    }

    private function toLookup(array $source, string $origin): array
    {
        $attributes = is_array($source['atributos'] ?? null) ? $source['atributos'] : [];
        $pick       = fn (string ...$keys) => $this->firstFilled($keys, $source, $attributes);

        return [
            'source'          => $origin,
            'sku'             => $this->clean($source['sku'] ?? null),
            'titulo'          => $this->clean($source['titulo'] ?? null),
            'titulo_meli'     => $this->clean($source['tituloMeli'] ?? null),
            'descripcion'     => $this->clean($source['descripcion'] ?? null),
            'imagenes'        => array_values(array_filter(array_map([$this, 'clean'], (array) ($source['imagenes'] ?? [])))),
            'precio'          => (float) ($source['precio'] ?? 0),
            'peso'            => (float) ($source['peso'] ?? 0),
            'marca'           => $pick('marca'),
            'modelo'          => $pick('modelo'),
            'ean'             => $pick('ean', 'EAN', 'upc', 'UPC', 'gtin'),
            'alto'            => $pick('alto'),
            'ancho'           => $pick('ancho'),
            'largo'           => $pick('largo'),
            'categoria_meli'  => $this->clean($source['categoriaMeli'] ?? null),
            'atributos_extra' => $this->extraAttributes($attributes['atributosExtra'] ?? $source['aAtributosExtras'] ?? []),
        ];
    }

    private function extraAttributes(mixed $extras): array
    {
        $result = [];

        foreach ((array) $extras as $extra) {
            $id    = $this->clean($extra['id'] ?? $extra['atributo'] ?? null);
            $value = $this->clean($extra['value_name'] ?? $extra['valor'] ?? $extra['value'] ?? null);

            if ($id) {
                $result[] = ['id' => $id, 'name' => $this->clean($extra['name'] ?? null) ?? $id, 'value' => $value ?? ''];
            }
        }

        return $result;
    }

    private function firstFilled(array $keys, array ...$sources): ?string
    {
        foreach ($sources as $source) {
            foreach ($keys as $key) {
                $value = $this->clean(isset($source[$key]) && is_scalar($source[$key]) ? (string) $source[$key] : null);
                if ($value !== null) {
                    return $value;
                }
            }
        }

        return null;
    }

    private function clean(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim(preg_replace('/\p{Cf}+/u', '', $value));

        return $value === '' ? null : $value;
    }
}
