<?php

namespace Tests\Unit;

use App\Events\MeliTokenRefreshed;
use App\Interfaces\MercadolibreAccountRepositoryInterface;
use App\Models\MercadolibreAccount;
use App\Services\MercadoLibreService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;

class MercadoLibreServiceTest extends TestCase
{
    private MercadolibreAccountRepositoryInterface&\Mockery\MockInterface $accountRepo;
    private MercadoLibreService $service;
    private MercadolibreAccount $account;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('services.mercadolibre.api_base_url', 'https://api.mercadolibre.com');
        Config::set('services.mercadolibre.token_url', 'https://api.mercadolibre.com/oauth/token');
        Config::set('services.mercadolibre.auth_url', 'https://auth.mercadolibre.com.co/authorization');
        Config::set('services.mercadolibre.client_id', 'test-client-id');
        Config::set('services.mercadolibre.client_secret', 'test-client-secret');
        Config::set('services.mercadolibre.redirect_uri', 'http://localhost/meli/callback');

        $this->accountRepo = Mockery::mock(MercadolibreAccountRepositoryInterface::class);
        $this->service     = new MercadoLibreService($this->accountRepo);

        $this->account = MercadolibreAccount::factory()->create([
            'access_token'  => 'test-access-token',
            'refresh_token' => 'test-refresh-token',
            'expires_at'    => Carbon::now()->addHours(2),
        ]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_get_products_returns_results(): void
    {
        Http::fake([
            'https://api.mercadolibre.com/users/*/items/search*' => Http::response([
                'results' => ['MCO001', 'MCO002'],
                'paging'  => ['total' => 2, 'limit' => 50, 'offset' => 0],
            ], 200),
        ]);

        $result = $this->service->getProducts($this->account);

        $this->assertArrayHasKey('results', $result);
        $this->assertCount(2, $result['results']);
    }

    public function test_get_product_returns_item_detail(): void
    {
        Http::fake([
            'https://api.mercadolibre.com/items/MCO001*' => Http::response([
                'id'                 => 'MCO001',
                'title'              => 'Producto Test',
                'price'              => 100000,
                'available_quantity' => 10,
                'status'             => 'active',
            ], 200),
        ]);

        $result = $this->service->getProduct($this->account, 'MCO001');

        $this->assertEquals('MCO001', $result['id']);
        $this->assertEquals('Producto Test', $result['title']);
    }

    public function test_get_orders_returns_order_results(): void
    {
        Http::fake([
            'https://api.mercadolibre.com/orders/search*' => Http::response([
                'results' => [
                    ['id' => 1001, 'status' => 'paid', 'total_amount' => 150000],
                    ['id' => 1002, 'status' => 'pending', 'total_amount' => 75000],
                ],
                'paging' => ['total' => 2, 'limit' => 50, 'offset' => 0],
            ], 200),
        ]);

        $result = $this->service->getOrders($this->account, ['sort' => 'date_desc']);

        $this->assertArrayHasKey('results', $result);
        $this->assertCount(2, $result['results']);
    }

    public function test_get_customer_returns_user_data(): void
    {
        Http::fake([
            'https://api.mercadolibre.com/users/123456*' => Http::response([
                'id'         => 123456,
                'first_name' => 'Carlos',
                'last_name'  => 'Pérez',
                'nickname'   => 'CARLITOP99',
                'email'      => 'carlos@email.com',
            ], 200),
        ]);

        $result = $this->service->getCustomer($this->account, '123456');

        $this->assertEquals('Carlos', $result['first_name']);
        $this->assertEquals('CARLITOP99', $result['nickname']);
    }

    public function test_refresh_token_updates_account_and_fires_event(): void
    {
        Event::fake([MeliTokenRefreshed::class]);

        Http::fake([
            'https://api.mercadolibre.com/oauth/token*' => Http::response([
                'access_token'  => 'new-access-token',
                'refresh_token' => 'new-refresh-token',
                'expires_in'    => 21600,
            ], 200),
        ]);

        $updatedAccount = MercadolibreAccount::factory()->make([
            'access_token'  => 'new-access-token',
            'refresh_token' => 'new-refresh-token',
        ]);

        $this->accountRepo
            ->shouldReceive('updateTokens')
            ->once()
            ->andReturn($updatedAccount);

        $this->service->refreshAccessToken($this->account);

        Event::assertDispatched(MeliTokenRefreshed::class);
    }

    public function test_auto_refresh_when_token_expires_soon(): void
    {
        $expiringSoon = MercadolibreAccount::factory()->create([
            'access_token'  => 'expiring-token',
            'refresh_token' => 'refresh-token',
            'expires_at'    => Carbon::now()->addMinutes(3),
        ]);

        Http::fake([
            'https://api.mercadolibre.com/oauth/token*' => Http::response([
                'access_token'  => 'fresh-token',
                'refresh_token' => 'new-refresh-token',
                'expires_in'    => 21600,
            ], 200),
            'https://api.mercadolibre.com/users/*/items/search*' => Http::response([
                'results' => [],
                'paging'  => ['total' => 0],
            ], 200),
        ]);

        $refreshedAccount = MercadolibreAccount::factory()->make([
            'access_token' => 'fresh-token',
            'expires_at'   => Carbon::now()->addHours(6),
        ]);

        $this->accountRepo
            ->shouldReceive('updateTokens')
            ->once()
            ->andReturn($refreshedAccount);

        $this->service->getProducts($expiringSoon);

        Http::assertSent(fn ($req) => str_contains($req->url(), 'oauth/token'));
    }

    public function test_retry_on_429_response(): void
    {
        Http::fake([
            'https://api.mercadolibre.com/users/*/items/search*' => Http::sequence()
                ->push(['error' => 'too_many_requests'], 429)
                ->push(['error' => 'too_many_requests'], 429)
                ->push(['results' => ['MCO001'], 'paging' => ['total' => 1]], 200),
        ]);

        $result = $this->service->getProducts($this->account);

        $this->assertArrayHasKey('results', $result);
        $this->assertEquals(['MCO001'], $result['results']);
    }
}
