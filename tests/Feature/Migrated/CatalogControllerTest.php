<?php

namespace Tests\Feature\Migrated;

use App\Interfaces\ExternalCatalogRepositoryInterface;
use App\Models\Product;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CatalogControllerTest extends TestCase
{
    private function catalogProduct(array $overrides = []): array
    {
        return array_merge([
            'titulo'        => 'Auriculares inalámbricos para ver TV con base de carga transmisora Bluetooth V53 sin retraso',
            'descripcion'   => 'Experiencia de visualización privada.',
            'imagenes'      => ['https://img.test/1.jpg', 'https://img.test/2.jpg'],
            'atributos'     => [
                'marca'          => "\u{200E}Huizhou Acoustic",
                'modelo'         => 'CH211',
                'alto'           => 7,
                'atributosExtra' => [['id' => 'INCLUDES_ASSEMBLY_MANUAL', 'name' => 'Incluye manual', 'value_name' => '']],
            ],
            'precio'        => 100,
            'sku'           => 'B0FN74Q978',
            'peso'          => 2,
            'categoriaMeli' => 'MCO441291',
            'tituloMeli'    => '',
        ], $overrides);
    }

    public function test_lookup_sku_reads_mongo_catalog_first(): void
    {
        [$admin] = $this->adminWithAccount();
        $this->setSettings(['dollar_price' => 4000]);
        $this->catalog->products = [$this->catalogProduct()];
        Http::fake();

        $this->getJson('/api/catalog/sku/b0fn74q978', $this->authHeaders($admin))
            ->assertOk()
            ->assertJsonPath('data.source', 'catalog')
            ->assertJsonPath('data.sku', 'B0FN74Q978')
            ->assertJsonPath('data.marca', 'Huizhou Acoustic')
            ->assertJsonPath('data.modelo', 'CH211')
            ->assertJsonPath('data.alto', '7')
            ->assertJsonPath('data.categoria_meli', 'MCO441291')
            ->assertJsonPath('data.atributos_extra.0.id', 'INCLUDES_ASSEMBLY_MANUAL')
            ->assertJsonPath('data.imagenes.1', 'https://img.test/2.jpg')
            ->assertJsonPath('data.final_price', 563000);

        Http::assertNothingSent();
    }

    public function test_lookup_matches_catalog_sku_with_invisible_characters(): void
    {
        [$admin] = $this->adminWithAccount();
        $this->catalog->products = [$this->catalogProduct(['sku' => "\u{200E}B08NM2GF2V"])];

        $this->getJson('/api/catalog/sku/B08NM2GF2V', $this->authHeaders($admin))
            ->assertOk()
            ->assertJsonPath('data.sku', 'B08NM2GF2V');
    }

    public function test_lookup_falls_back_to_scraper_when_not_in_catalog(): void
    {
        [$admin] = $this->adminWithAccount();
        $this->setSettings([
            'scraping_url'     => 'https://scraper.test/',
            'dollar_price'     => 4000,
            'default_quantity' => 3,
        ]);

        Http::fake([
            'scraper.test/api/v1/app/scriping/B0TEST' => Http::response(['titulo' => 'Audífonos', 'precio' => 100, 'peso' => 2]),
        ]);

        $this->getJson('/api/catalog/sku/B0TEST', $this->authHeaders($admin))
            ->assertOk()
            ->assertJsonPath('data.source', 'scraper')
            ->assertJsonPath('data.titulo', 'Audífonos')
            ->assertJsonPath('data.final_price', 563000)
            ->assertJsonPath('data.default_quantity', 3)
            ->assertJsonStructure(['data' => ['price_breakdown' => ['subtotal_usd', 'dollar_price']]]);
    }

    public function test_lookup_works_without_meli_account(): void
    {
        $admin = $this->createAdmin();
        $this->catalog->products = [$this->catalogProduct()];

        $this->getJson('/api/catalog/sku/B0FN74Q978', $this->authHeaders($admin))
            ->assertOk()
            ->assertJsonPath('data.source', 'catalog');
    }

    public function test_lookup_sku_rejects_already_published_sku(): void
    {
        [$admin, $account] = $this->adminWithAccount();
        Product::factory()->create(['mercadolibre_account_id' => $account->id, 'sku' => 'B0FN74Q978']);
        $this->catalog->products = [$this->catalogProduct()];

        $this->getJson('/api/catalog/sku/B0FN74Q978', $this->authHeaders($admin))
            ->assertUnprocessable()
            ->assertJsonPath('message', 'El producto ya se encuentra registrado.');
    }

    public function test_lookup_sku_reports_not_found(): void
    {
        [$admin] = $this->adminWithAccount();
        Http::fake();

        $this->getJson('/api/catalog/sku/NOPE', $this->authHeaders($admin))
            ->assertUnprocessable()
            ->assertJsonPath('message', 'No se encontró el producto en el catálogo.');

        Http::assertNothingSent();
    }

    public function test_lookup_reports_catalog_outage_without_scraper(): void
    {
        [$admin] = $this->adminWithAccount();
        $this->mock(ExternalCatalogRepositoryInterface::class)
            ->shouldReceive('findBySku')->andThrow(new \RuntimeException('connection timeout'));

        $this->getJson('/api/catalog/sku/B0X', $this->authHeaders($admin))
            ->assertUnprocessable()
            ->assertJsonPath('message', 'No fue posible consultar el catálogo de productos. Verifica la conexión con MongoDB.');
    }

    public function test_by_category_reads_external_catalog(): void
    {
        [$admin] = $this->adminWithAccount();

        $this->mock(ExternalCatalogRepositoryInterface::class)
            ->shouldReceive('findByCategory')->once()->with('MCO1234')
            ->andReturn([['titulo' => 'Mouse', 'categoriaMeli' => 'MCO1234']]);

        $this->getJson('/api/catalog/category/MCO1234', $this->authHeaders($admin))
            ->assertOk()
            ->assertJsonPath('data.0.titulo', 'Mouse');
    }

    public function test_by_category_returns_404_when_empty(): void
    {
        [$admin] = $this->adminWithAccount();

        $this->mock(ExternalCatalogRepositoryInterface::class)
            ->shouldReceive('findByCategory')->andReturn([]);

        $this->getJson('/api/catalog/category/MCO0', $this->authHeaders($admin))->assertNotFound();
    }

    public function test_search_by_title(): void
    {
        [$admin] = $this->adminWithAccount();

        $this->mock(ExternalCatalogRepositoryInterface::class)
            ->shouldReceive('searchByTitle')->once()->with('teclado')
            ->andReturn([['titulo' => 'Teclado mecánico']]);

        $this->getJson('/api/catalog/search?title=teclado', $this->authHeaders($admin))
            ->assertOk()
            ->assertJsonPath('data.0.titulo', 'Teclado mecánico');
    }

    public function test_search_validates_title(): void
    {
        [$admin] = $this->adminWithAccount();

        $this->getJson('/api/catalog/search', $this->authHeaders($admin))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['title']);
    }

    public function test_catalog_is_forbidden_for_operator(): void
    {
        [$operator] = $this->operatorWithAccount();

        $this->getJson('/api/catalog/category/MCO1', $this->authHeaders($operator))->assertForbidden();
    }
}
