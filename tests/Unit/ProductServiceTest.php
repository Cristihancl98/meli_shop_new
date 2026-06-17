<?php

namespace Tests\Unit;

use App\DTOs\ProductDTO;
use App\Events\ProductSynced;
use App\Interfaces\ProductRepositoryInterface;
use App\Models\MercadolibreAccount;
use App\Models\Product;
use App\Services\MercadoLibreService;
use App\Services\ProductService;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;

class ProductServiceTest extends TestCase
{
    private ProductRepositoryInterface&\Mockery\MockInterface $productRepo;
    private MercadoLibreService&\Mockery\MockInterface $meliService;
    private ProductService $service;
    private MercadolibreAccount $account;

    protected function setUp(): void
    {
        parent::setUp();

        $this->account = MercadolibreAccount::factory()->create();

        $this->productRepo = Mockery::mock(ProductRepositoryInterface::class);
        $this->meliService = Mockery::mock(MercadoLibreService::class);
        $this->service     = new ProductService($this->productRepo, $this->meliService);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_list_delegates_to_repository(): void
    {
        $paginator = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 20);

        $this->productRepo
            ->shouldReceive('paginate')
            ->once()
            ->with($this->account->id, ['search' => 'test'])
            ->andReturn($paginator);

        $result = $this->service->list($this->account, ['search' => 'test']);

        $this->assertSame($paginator, $result);
    }

    public function test_find_returns_product(): void
    {
        $product = Product::factory()->make(['id' => 1]);

        $this->productRepo
            ->shouldReceive('find')
            ->once()
            ->with(1)
            ->andReturn($product);

        $result = $this->service->find(1);

        $this->assertSame($product, $result);
    }

    public function test_find_returns_null_for_missing_product(): void
    {
        $this->productRepo
            ->shouldReceive('find')
            ->once()
            ->with(999)
            ->andReturnNull();

        $result = $this->service->find(999);

        $this->assertNull($result);
    }

    public function test_delete_closes_product_on_meli_and_soft_deletes(): void
    {
        $product = Product::factory()->make(['meli_item_id' => 'MCO123']);

        $this->meliService
            ->shouldReceive('updateProduct')
            ->once()
            ->with($this->account, 'MCO123', ['status' => 'closed'])
            ->andReturn([]);

        $this->productRepo
            ->shouldReceive('delete')
            ->once()
            ->with($product)
            ->andReturn(true);

        $result = $this->service->delete($this->account, $product);

        $this->assertTrue($result);
    }

    public function test_delete_skips_meli_call_if_no_meli_item_id(): void
    {
        $product = Product::factory()->make(['meli_item_id' => null]);

        $this->meliService->shouldNotReceive('updateProduct');

        $this->productRepo
            ->shouldReceive('delete')
            ->once()
            ->andReturn(true);

        $this->service->delete($this->account, $product);
    }

    public function test_create_dispatches_product_synced_event(): void
    {
        Event::fake([ProductSynced::class]);

        Http::fake([
            'api.mercadolibre.com/*' => Http::response([
                'id'        => 'MCO999',
                'permalink' => 'https://articulo.mercadolibre.com.co/test',
            ], 200),
        ]);

        $product = Product::factory()->create([
            'mercadolibre_account_id' => $this->account->id,
            'meli_item_id'            => null,
        ]);

        $this->productRepo
            ->shouldReceive('create')
            ->once()
            ->andReturn($product);

        $this->productRepo
            ->shouldReceive('update')
            ->andReturn($product);

        $this->meliService
            ->shouldReceive('createProduct')
            ->once()
            ->andReturn(['id' => 'MCO999', 'permalink' => 'https://test.com']);

        $dto = new ProductDTO(
            title: 'Test',
            price: 100000,
            stock: 5,
            status: 'active',
            condition: 'new',
            listingTypeId: 'gold_special',
        );

        $this->service->create($this->account, $dto);

        Event::assertDispatched(ProductSynced::class);
    }

    public function test_sync_from_meli_updates_product_and_fires_event(): void
    {
        Event::fake([ProductSynced::class]);

        $product = Product::factory()->create([
            'mercadolibre_account_id' => $this->account->id,
            'meli_item_id'            => 'MCO123456',
        ]);

        $meliResponse = [
            'title'              => 'Updated Title',
            'price'              => 200000,
            'available_quantity' => 20,
            'status'             => 'active',
            'thumbnail'          => 'https://img.meli.com/test.jpg',
            'permalink'          => 'https://articulo.meli.com/test',
        ];

        $this->meliService
            ->shouldReceive('getProduct')
            ->once()
            ->with($this->account, 'MCO123456')
            ->andReturn($meliResponse);

        $this->productRepo
            ->shouldReceive('update')
            ->once()
            ->andReturn($product);

        $this->service->syncFromMeli($this->account, $product);

        Event::assertDispatched(ProductSynced::class);
    }
}
