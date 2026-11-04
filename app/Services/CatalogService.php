<?php

namespace App\Services;

use App\Enums\ScrapingStatus;
use App\Enums\SettingKey;
use App\Exceptions\BusinessRuleException;
use App\Interfaces\ExternalCatalogRepositoryInterface;
use App\Jobs\ScrapeSkuJob;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CatalogService
{
    public const SOURCE_CATALOG = 'catalog';
    public const SOURCE_SCRAPER = 'scraper';

    private const SCRAPING_PATH = 'api/v1/app/scriping/';

    private const SYNC_SCRAPING_TIMEOUT = 60;

    private const BACKGROUND_SCRAPING_TIMEOUT = 240;

    private const SCRAPING_CACHE_MINUTES = 30;

    public function __construct(
        private readonly ExternalCatalogRepositoryInterface $catalogRepository,
        private readonly SettingService $settingService,
        private readonly TenantManager $tenantManager
    ) {}

    public function findProductBySku(string $sku, bool $allowLiveScraping = true): ?array
    {
        $document = $this->findInCatalog($sku);

        if ($document) {
            return $this->toLookup($document, self::SOURCE_CATALOG);
        }

        $scraped = $this->scrapedResult($sku);

        if (!$scraped && $allowLiveScraping && $this->scrapingEnabled()) {
            $scraped = $this->scrapeSku($sku);
        }

        return $scraped ? $this->toLookup($scraped, self::SOURCE_SCRAPER) : null;
    }

    public function scrapingEnabled(): bool
    {
        return (bool) $this->settingService->get(SettingKey::ScrapingUrl);
    }

    public function scrapingStatus(string $sku): ScrapingStatus
    {
        return ScrapingStatus::tryFrom(Cache::get($this->scrapingCacheKey($sku))['status'] ?? '') ?? ScrapingStatus::None;
    }

    public function startScraping(string $sku): void
    {
        if ($this->scrapingStatus($sku) === ScrapingStatus::Pending) {
            return;
        }

        $this->rememberScraping($sku, ScrapingStatus::Pending);

        ScrapeSkuJob::dispatch($sku);
    }

    public function runScraping(string $sku): void
    {
        $data = $this->scrapeSku($sku, self::BACKGROUND_SCRAPING_TIMEOUT);

        $data
            ? $this->rememberScraping($sku, ScrapingStatus::Done, $data)
            : $this->markScrapingFailed($sku);
    }

    public function markScrapingFailed(string $sku): void
    {
        $this->rememberScraping($sku, ScrapingStatus::Failed);
    }

    public function forgetScraping(string $sku): void
    {
        Cache::forget($this->scrapingCacheKey($sku));
    }

    private function scrapedResult(string $sku): ?array
    {
        $cached = Cache::get($this->scrapingCacheKey($sku));

        return ($cached['status'] ?? null) === ScrapingStatus::Done->value ? $cached['data'] : null;
    }

    private function rememberScraping(string $sku, ScrapingStatus $status, ?array $data = null): void
    {
        Cache::put(
            $this->scrapingCacheKey($sku),
            ['status' => $status->value, 'data' => $data],
            now()->addMinutes(self::SCRAPING_CACHE_MINUTES)
        );
    }

    private function scrapingCacheKey(string $sku): string
    {
        return 'scraping:' . ($this->tenantManager->current()?->id ?? 0) . ':' . strtoupper(trim($sku));
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

    public function scrapeSku(string $sku, int $timeout = self::SYNC_SCRAPING_TIMEOUT): ?array
    {
        $baseUrl = $this->settingService->get(SettingKey::ScrapingUrl);

        if (!$baseUrl) {
            throw new BusinessRuleException('No hay URL de scraping configurada para esta tienda.');
        }

        try {
            $response = Http::timeout($timeout)->get(rtrim($baseUrl, '/') . '/' . self::SCRAPING_PATH . urlencode($sku));
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
