<?php

namespace App\Http\Controllers\Api;

use App\DTOs\PublishProductDTO;
use App\DTOs\UpdateListingDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Product\PublishProductRequest;
use App\Http\Requests\Product\UpdateListingRequest;
use App\Interfaces\ProductRepositoryInterface;
use App\Models\Product;
use App\Services\ProductPublishingService;
use App\Traits\ApiResponses;
use App\Traits\ResolvesMeliAccount;
use Illuminate\Http\JsonResponse;

class ProductPublishingController extends Controller
{
    use ApiResponses, ResolvesMeliAccount;

    public function __construct(
        private readonly ProductPublishingService $publishingService,
        private readonly ProductRepositoryInterface $productRepository
    ) {}

    public function summary(): JsonResponse
    {
        $this->authorize('viewAny', Product::class);

        if (!$account = $this->currentAccount()) {
            return $this->noAccount();
        }

        return $this->ok($this->publishingService->summary($account), 'Resumen de productos obtenido.');
    }

    public function findBySku(string $sku): JsonResponse
    {
        $this->authorize('viewAny', Product::class);

        if (!$account = $this->currentAccount()) {
            return $this->noAccount();
        }

        $products = $this->publishingService->findLocalBySku($account, $sku);

        return $this->ok(['exists' => $products->isNotEmpty(), 'products' => $products], 'Búsqueda por SKU completada.');
    }

    public function publish(PublishProductRequest $request): JsonResponse
    {
        $this->authorize('publish', Product::class);

        if (!$account = $this->currentAccount()) {
            return $this->noAccount();
        }

        $product = $this->publishingService->publish($account, PublishProductDTO::fromArray($request->validated()), $request->user());

        return $this->ok($product, 'Producto publicado en Mercado Libre.', 201);
    }

    public function pause(int $id): JsonResponse
    {
        return $this->changeStatus($id, 'paused', 'Producto pausado.');
    }

    public function activate(int $id): JsonResponse
    {
        return $this->changeStatus($id, 'active', 'Producto activado.');
    }

    public function updateListing(UpdateListingRequest $request, int $id): JsonResponse
    {
        if (!$account = $this->currentAccount()) {
            return $this->noAccount();
        }

        if (!$product = $this->productRepository->findForAccount($account->id, $id)) {
            return $this->notFound('Producto no encontrado.');
        }

        $this->authorize('update', $product);

        $updated = $this->publishingService->updateListing($account, $product, UpdateListingDTO::fromArray($request->validated()));

        return $this->ok($updated, 'Producto actualizado correctamente.');
    }

    public function archive(int $id): JsonResponse
    {
        if (!$account = $this->currentAccount()) {
            return $this->noAccount();
        }

        if (!$product = $this->productRepository->findForAccount($account->id, $id)) {
            return $this->notFound('Producto no encontrado.');
        }

        $this->authorize('archive', $product);
        $this->publishingService->archive($product);

        return $this->ok(null, 'Producto archivado.');
    }

    private function changeStatus(int $id, string $status, string $message): JsonResponse
    {
        if (!$account = $this->currentAccount()) {
            return $this->noAccount();
        }

        if (!$product = $this->productRepository->findForAccount($account->id, $id)) {
            return $this->notFound('Producto no encontrado.');
        }

        $this->authorize('changeStatus', $product);

        return $this->ok($this->publishingService->changeStatus($account, $product, $status), $message);
    }
}
