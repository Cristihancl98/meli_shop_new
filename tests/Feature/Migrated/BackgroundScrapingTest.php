<?php

namespace Tests\Feature\Migrated;

use App\Jobs\ScrapeSkuJob;
use App\Services\CatalogService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class BackgroundScrapingTest extends TestCase
{
    private const SCRAPER_URL = 'scraper.test/api/v1/app/scriping/B0SCRAPE';

    protected function setUp(): void
    {
        parent::setUp();
        $this->setSettings(['scraping_url' => 'https://scraper.test/', 'dollar_price' => 4000]);
    }

    public function test_unknown_sku_dispatches_job_and_shows_loading_modal(): void
    {
        Queue::fake();
        [$admin] = $this->adminWithAccount();

        $this->actingAs($admin)->get(route('publisher.create', ['sku' => 'B0SCRAPE']))
            ->assertOk()
            ->assertSee('id="loading-modal"', false)
            ->assertSee('data-scraping-url="' . route('publisher.scraping-status', ['sku' => 'B0SCRAPE']) . '"', false)
            ->assertSee('Se está consultando en Amazon en segundo plano');

        Queue::assertPushed(ScrapeSkuJob::class, fn ($job) => $job->sku === 'B0SCRAPE');

        $this->getJson(route('publisher.scraping-status', ['sku' => 'B0SCRAPE']))
            ->assertOk()
            ->assertJsonPath('data.status', 'pending');
    }

    public function test_pending_scraping_is_not_dispatched_twice(): void
    {
        Queue::fake();
        [$admin] = $this->adminWithAccount();

        $this->actingAs($admin)->get(route('publisher.create', ['sku' => 'B0SCRAPE']));
        $this->actingAs($admin)->get(route('publisher.create', ['sku' => 'B0SCRAPE']));

        Queue::assertPushed(ScrapeSkuJob::class, 1);
    }

    public function test_finished_job_result_is_shown_without_scraping_again(): void
    {
        Http::fake([self::SCRAPER_URL => Http::response(['titulo' => 'Fitbit Inspire 3', 'precio' => '69.95', 'peso' => '0.12', 'sku' => 'B0SCRAPE'])]);
        [$admin] = $this->adminWithAccount();

        (new ScrapeSkuJob('B0SCRAPE'))->handle(app(CatalogService::class));

        $this->actingAs($admin)->getJson(route('publisher.scraping-status', ['sku' => 'B0SCRAPE']))
            ->assertJsonPath('data.status', 'done');

        $this->actingAs($admin)->get(route('publisher.create', ['sku' => 'B0SCRAPE']))
            ->assertOk()
            ->assertSee('Producto obtenido del servicio de scraping.')
            ->assertSee('value="69.95"', false)
            ->assertDontSee('data-scraping-url', false);

        $this->assertCount(1, Http::recorded(fn ($request) => str_contains($request->url(), 'scraper.test')));
    }

    public function test_failed_job_shows_error_and_allows_retry(): void
    {
        Queue::fake();
        Http::fake([self::SCRAPER_URL => Http::response(['error' => 'En la consulta'], 501)]);
        [$admin] = $this->adminWithAccount();

        (new ScrapeSkuJob('B0SCRAPE'))->handle(app(CatalogService::class));

        $this->actingAs($admin)->get(route('publisher.create', ['sku' => 'B0SCRAPE']))
            ->assertOk()
            ->assertSee('No fue posible obtener el producto desde Amazon');

        $this->actingAs($admin)->get(route('publisher.create', ['sku' => 'B0SCRAPE']))
            ->assertSee('data-scraping-url', false);

        Queue::assertPushed(ScrapeSkuJob::class, 1);
    }

    public function test_crashed_job_marks_scraping_as_failed(): void
    {
        $this->actingAs($this->createAdmin());

        (new ScrapeSkuJob('B0SCRAPE'))->failed(new \RuntimeException('timeout'));

        $this->getJson(route('publisher.scraping-status', ['sku' => 'B0SCRAPE']))
            ->assertJsonPath('data.status', 'failed');
    }

    public function test_status_requires_publish_permission(): void
    {
        $this->actingAs($this->createOperator());

        $this->getJson(route('publisher.scraping-status', ['sku' => 'B0SCRAPE']))->assertForbidden();
    }

    public function test_catalog_products_render_directly(): void
    {
        Queue::fake();
        [$admin] = $this->adminWithAccount();
        $this->catalog->products = [['titulo' => 'Lámpara', 'precio' => 10, 'peso' => 1, 'sku' => 'B0LAMP']];

        $this->actingAs($admin)->get(route('publisher.create', ['sku' => 'B0LAMP']))
            ->assertOk()
            ->assertSee('Producto encontrado en el catálogo')
            ->assertDontSee('data-scraping-url', false);

        Queue::assertNothingPushed();
    }
}
