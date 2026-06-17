<?php

namespace App\Services;

use App\Events\MeliTokenRefreshed;
use App\Events\SyncFailed;
use App\Interfaces\MercadolibreAccountRepositoryInterface;
use App\Models\MercadolibreAccount;
use Carbon\Carbon;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MercadoLibreService
{
    private string $apiBaseUrl;
    private string $clientId;
    private string $clientSecret;
    private string $redirectUri;

    private const RETRY_DELAYS = [1, 3, 10];

    public function __construct(
        private readonly MercadolibreAccountRepositoryInterface $accountRepository
    ) {
        $this->apiBaseUrl    = config('services.mercadolibre.api_base_url');
        $this->clientId      = config('services.mercadolibre.client_id');
        $this->clientSecret  = config('services.mercadolibre.client_secret');
        $this->redirectUri   = config('services.mercadolibre.redirect_uri');
    }

    // -----------------------------------------------------------------------
    // OAuth2
    // -----------------------------------------------------------------------

    public function getAuthorizationUrl(): string
    {
        $params = http_build_query([
            'response_type' => 'code',
            'client_id'     => $this->clientId,
            'redirect_uri'  => $this->redirectUri,
        ]);

        return config('services.mercadolibre.auth_url') . '?' . $params;
    }

    public function exchangeCodeForTokens(string $code): array
    {
        $response = Http::post(config('services.mercadolibre.token_url'), [
            'grant_type'    => 'authorization_code',
            'client_id'     => $this->clientId,
            'client_secret' => $this->clientSecret,
            'code'          => $code,
            'redirect_uri'  => $this->redirectUri,
        ]);

        if (!$response->successful()) {
            throw new \RuntimeException('Error al intercambiar el código de autorización: ' . $response->body());
        }

        return $response->json();
    }

    public function refreshAccessToken(MercadolibreAccount $account): MercadolibreAccount
    {
        $response = Http::post(config('services.mercadolibre.token_url'), [
            'grant_type'    => 'refresh_token',
            'client_id'     => $this->clientId,
            'client_secret' => $this->clientSecret,
            'refresh_token' => $account->refresh_token,
        ]);

        if (!$response->successful()) {
            event(new SyncFailed($account, 'refresh_token', 'Error al renovar el token: ' . $response->body()));
            throw new \RuntimeException('Error al renovar el token de acceso.');
        }

        $data    = $response->json();
        $updated = $this->accountRepository->updateTokens(
            $account,
            $data['access_token'],
            $data['refresh_token'],
            Carbon::now()->addSeconds($data['expires_in'])
        );

        event(new MeliTokenRefreshed($updated));

        return $updated;
    }

    public function getUserInfo(string $accessToken): array
    {
        $response = Http::withToken($accessToken)
            ->get($this->apiBaseUrl . '/users/me');

        return $response->successful() ? $response->json() : [];
    }

    // -----------------------------------------------------------------------
    // Productos
    // -----------------------------------------------------------------------

    public function getProducts(MercadolibreAccount $account, int $offset = 0, int $limit = 100): array
    {
        $response = $this->request($account, 'GET', "/users/{$account->meli_user_id}/items/search", [
            'offset' => $offset,
            'limit'  => $limit,
        ]);

        return $response->json();
    }

    public function getProduct(MercadolibreAccount $account, string $itemId): array
    {
        $response = $this->request($account, 'GET', "/items/{$itemId}");

        return $response->json();
    }

    public function createProduct(MercadolibreAccount $account, array $data): array
    {
        $response = $this->request($account, 'POST', '/items', [], $data);

        return $response->json();
    }

    public function updateProduct(MercadolibreAccount $account, string $itemId, array $data): array
    {
        $response = $this->request($account, 'PUT', "/items/{$itemId}", [], $data);

        return $response->json();
    }

    // -----------------------------------------------------------------------
    // Órdenes
    // -----------------------------------------------------------------------

    public function getOrders(MercadolibreAccount $account, array $filters = []): array
    {
        $query = array_merge([
            'seller'  => $account->meli_user_id,
            'sort'    => 'date_desc',
            'limit'   => 50,
            'offset'  => 0,
        ], $filters);

        $response = $this->request($account, 'GET', '/orders/search', $query);

        return $response->json();
    }

    public function getOrder(MercadolibreAccount $account, string $orderId): array
    {
        $response = $this->request($account, 'GET', "/orders/{$orderId}");

        return $response->json();
    }

    // -----------------------------------------------------------------------
    // Preguntas
    // -----------------------------------------------------------------------

    public function getQuestions(MercadolibreAccount $account, array $filters = []): array
    {
        $response = $this->request($account, 'GET', '/my/received_questions/search', $filters);

        return $response->json();
    }

    // -----------------------------------------------------------------------
    // Clientes
    // -----------------------------------------------------------------------

    public function getCustomer(MercadolibreAccount $account, string $customerId): array
    {
        $response = $this->request($account, 'GET', "/users/{$customerId}");

        return $response->json();
    }

    // -----------------------------------------------------------------------
    // Imágenes
    // -----------------------------------------------------------------------

    public function uploadImage(MercadolibreAccount $account, string $filePath): array
    {
        $account = $this->ensureFreshToken($account);

        $response = Http::withToken($account->access_token)
            ->attach('file', fopen($filePath, 'r'), basename($filePath))
            ->post("{$this->apiBaseUrl}/pictures/items/upload");

        if (!$response->successful()) {
            throw new \RuntimeException('Error al subir imagen a Mercado Libre: ' . $response->body());
        }

        return $response->json();
    }

    // -----------------------------------------------------------------------
    // HTTP con retry y auto-refresh
    // -----------------------------------------------------------------------

    private function request(
        MercadolibreAccount $account,
        string $method,
        string $path,
        array $query = [],
        array $body = []
    ): Response {
        $account = $this->ensureFreshToken($account);

        $attempt = 0;

        retry:
        $http = Http::withToken($account->access_token);

        $response = match (strtoupper($method)) {
            'GET'    => $http->get("{$this->apiBaseUrl}{$path}", $query),
            'POST'   => $http->post("{$this->apiBaseUrl}{$path}", $body),
            'PUT'    => $http->put("{$this->apiBaseUrl}{$path}", $body),
            'DELETE' => $http->delete("{$this->apiBaseUrl}{$path}"),
            default  => throw new \InvalidArgumentException("Método HTTP no soportado: {$method}"),
        };

        if ($response->status() === 429 && $attempt < count(self::RETRY_DELAYS)) {
            sleep(self::RETRY_DELAYS[$attempt]);
            $attempt++;
            goto retry;
        }

        if (!$response->successful()) {
            Log::warning("MeLi API error [{$method} {$path}]", [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
        }

        return $response;
    }

    private function ensureFreshToken(MercadolibreAccount $account): MercadolibreAccount
    {
        if ($account->isTokenExpired()) {
            return $this->refreshAccessToken($account);
        }

        return $account;
    }
}
