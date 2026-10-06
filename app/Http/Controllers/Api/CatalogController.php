<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Catalog\SearchCatalogRequest;
use App\Models\Product;
use App\Services\CatalogService;
use App\Services\ProductPublishingService;
use App\Traits\ApiResponses;
use App\Traits\ResolvesMeliAccount;
use Illuminate\Http\JsonResponse;

class CatalogController extends Controller
{
    use ApiResponses, ResolvesMeliAccount;

    public function __construct(
        private readonly CatalogService $catalogService,
        private readonly ProductPublishingService $publishingService
    ) {}

    public function lookupSku(string $sku): JsonResponse
    {
        $this->authorize('publish', Product::class);

        return $this->ok($this->publishingService->lookupSku($this->currentAccount(), $sku), 'Producto encontrado.');
    }

    public function byCategory(string $categoryId): JsonResponse
    {
        $this->authorize('publish', Product::class);

        $products = $this->catalogService->findByCategory($categoryId);

        return $products === []
            ? $this->notFound('No se encontraron productos en esta categoría.')
            : $this->ok($products, 'Productos del catálogo obtenidos.');
    }

    public function searchByTitle(SearchCatalogRequest $request): JsonResponse
    {
        $this->authorize('publish', Product::class);

        $products = $this->catalogService->searchByTitle($request->validated('title'));

        return $products === []
            ? $this->notFound('No se encontraron productos con ese título.')
            : $this->ok($products, 'Productos del catálogo obtenidos.');
    }
}
