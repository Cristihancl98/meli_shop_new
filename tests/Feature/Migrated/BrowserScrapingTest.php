<?php

namespace Tests\Feature\Migrated;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BrowserScrapingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->setSettings(['scraping_url' => 'https://scraper.test/', 'dollar_price' => 4000]);
        Http::fake();
    }

    public function test_unknown_sku_hands_scraping_to_the_browser(): void
    {
        [$admin] = $this->adminWithAccount();

        $this->actingAs($admin)->get(route('publisher.create', ['sku' => 'B0SCRAPE']))
            ->assertOk()
            ->assertSee('id="loading-modal"', false)
            ->assertSee('data-scraper-url="https://scraper.test/api/v1/app/scriping/B0SCRAPE"', false)
            ->assertSee('data-reload-url="' . e(route('publisher.create', ['sku' => 'B0SCRAPE', 'scraped' => 1])) . '"', false)
            ->assertSee('a través del servicio de scraping');

        Http::assertNothingSent();
    }

    public function test_missing_product_after_scraping_shows_error_instead_of_looping(): void
    {
        [$admin] = $this->adminWithAccount();

        $this->actingAs($admin)->get(route('publisher.create', ['sku' => 'B0SCRAPE', 'scraped' => 1]))
            ->assertOk()
            ->assertSee('el producto no aparece en el catálogo')
            ->assertDontSee('data-scraper-url', false);
    }

    public function test_scraped_product_saved_in_catalog_is_rendered(): void
    {
        [$admin] = $this->adminWithAccount();
        $this->catalog->products = [['titulo' => 'Fitbit Inspire 3', 'precio' => '69.95', 'peso' => '0.12', 'sku' => 'B0SCRAPE']];

        $this->actingAs($admin)->get(route('publisher.create', ['sku' => 'B0SCRAPE', 'scraped' => 1]))
            ->assertOk()
            ->assertSee('Producto encontrado en el catálogo')
            ->assertSee('value="69.95"', false)
            ->assertDontSee('data-scraper-url', false);
    }

    public function test_sku_is_url_encoded_in_scraper_url(): void
    {
        [$admin] = $this->adminWithAccount();

        $this->actingAs($admin)->get(route('publisher.create', ['sku' => 'B0 X/1']))
            ->assertSee('data-scraper-url="https://scraper.test/api/v1/app/scriping/B0%20X%2F1"', false);
    }

    public function test_without_scraping_url_reports_not_found(): void
    {
        $this->setSettings(['scraping_url' => null]);
        [$admin] = $this->adminWithAccount();

        $this->actingAs($admin)->get(route('publisher.create', ['sku' => 'B0SCRAPE']))
            ->assertOk()
            ->assertSee('No se encontró el producto en el catálogo.')
            ->assertDontSee('data-scraper-url', false);
    }
}
