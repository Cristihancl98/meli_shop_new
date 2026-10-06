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

    public function refreshIfExpiring(MercadolibreAccount $account): MercadolibreAccount
    {
        return $this->ensureFreshToken($account);
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

    public function getSeller(MercadolibreAccount $account): array
    {
        return $this->request($account, 'GET', "/users/{$account->meli_user_id}")->json() ?? [];
    }

    // -----------------------------------------------------------------------
    // Publicaciones (complementos)
    // -----------------------------------------------------------------------

    public function scanItems(MercadolibreAccount $account, ?string $scrollId = null): array
    {
        $query = ['search_type' => 'scan', 'limit' => 50];

        if ($scrollId) {
            $query['scroll_id'] = $scrollId;
        }

        return $this->request($account, 'GET', "/users/{$account->meli_user_id}/items/search", $query)->json() ?? [];
    }

    public function getItems(MercadolibreAccount $account, array $itemIds): array
    {
        if ($itemIds === []) {
            return [];
        }

        return $this->request($account, 'GET', '/items', ['ids' => implode(',', $itemIds)])->json() ?? [];
    }

    public function createItemDescription(MercadolibreAccount $account, string $itemId, string $text): array
    {
        return $this->request($account, 'POST', "/items/{$itemId}/description", [], ['plain_text' => $text])->json() ?? [];
    }

    public function updateItemDescription(MercadolibreAccount $account, string $itemId, string $text): array
    {
        return $this->request($account, 'PUT', "/items/{$itemId}/description?api_version=2", [], ['plain_text' => $text])->json() ?? [];
    }

    public function changeItemStatus(MercadolibreAccount $account, string $itemId, string $status): array
    {
        return $this->updateProduct($account, $itemId, ['status' => $status]);
    }

    // -----------------------------------------------------------------------
    // Categorías
    // -----------------------------------------------------------------------

    public function getSiteCategories(MercadolibreAccount $account): array
    {
        $site = config('services.mercadolibre.country_code');

        return $this->request($account, 'GET', "/sites/{$site}/categories")->json() ?? [];
    }

    public function getCategory(MercadolibreAccount $account, string $categoryId): array
    {
        return $this->request($account, 'GET', "/categories/{$categoryId}")->json() ?? [];
    }

    public function predictCategory(MercadolibreAccount $account, string $title): array
    {
        $site = config('services.mercadolibre.country_code');

        return $this->request($account, 'GET', "/sites/{$site}/domain_discovery/search", [
            'q'     => $title,
            'limit' => 1,
        ])->json() ?? [];
    }

    // -----------------------------------------------------------------------
    // Envíos
    // -----------------------------------------------------------------------

    public function getShippingLabel(MercadolibreAccount $account, string $shipmentId): Response
    {
        return $this->request($account, 'GET', '/shipment_labels', [
            'shipment_ids' => $shipmentId,
            'savePdf'      => 'Y',
        ]);
    }

    // -----------------------------------------------------------------------
    // Preguntas preventa
    // -----------------------------------------------------------------------

    public function searchSellerQuestions(MercadolibreAccount $account, int $limit = 20): array
    {
        return $this->request($account, 'GET', '/questions/search', [
            'seller_id'   => $account->meli_user_id,
            'api_version' => 4,
            'sort_fields' => 'date_created',
            'sort_types'  => 'DESC',
            'limit'       => $limit,
        ])->json() ?? [];
    }

    public function getQuestion(MercadolibreAccount $account, string $questionId): array
    {
        return $this->request($account, 'GET', "/questions/{$questionId}", ['api_version' => 4])->json() ?? [];
    }

    public function answerQuestion(MercadolibreAccount $account, string $questionId, string $text): array
    {
        return $this->request($account, 'POST', '/answers', [], [
            'question_id' => $questionId,
            'text'        => $text,
        ])->json() ?? [];
    }

    // -----------------------------------------------------------------------
    // Mensajería posventa
    // -----------------------------------------------------------------------

    public function sendPostSaleMessage(MercadolibreAccount $account, string $packId, string $buyerId, string $text): array
    {
        return $this->request(
            $account,
            'POST',
            "/messages/packs/{$packId}/sellers/{$account->meli_user_id}?tag=post_sale",
            [],
            [
                'from' => ['user_id' => $account->meli_user_id],
                'to'   => ['user_id' => $buyerId],
                'text' => $text,
            ]
        )->json() ?? [];
    }

    public function getPackMessages(MercadolibreAccount $account, string $packId): array
    {
        return $this->request($account, 'GET', "/messages/packs/{$packId}/sellers/{$account->meli_user_id}", [
            'tag'          => 'post_sale',
            'mark_as_read' => 'false',
        ])->json() ?? [];
    }

    public function getUnreadPostSaleMessages(MercadolibreAccount $account): array
    {
        return $this->request($account, 'GET', '/messages/unread', [
            'role' => 'seller',
            'tag'  => 'post_sale',
        ])->json() ?? [];
    }

    public function getMessage(MercadolibreAccount $account, string $messageId): array
    {
        return $this->request($account, 'GET', "/messages/{$messageId}", ['tag' => 'post_sale'])->json() ?? [];
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
