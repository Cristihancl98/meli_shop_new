<?php

namespace App\Http\Controllers\Api;

use App\DTOs\ProductDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Interfaces\MercadolibreAccountRepositoryInterface;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class ProductController extends Controller
{
    public function __construct(
        private readonly ProductService $productService,
        private readonly MercadolibreAccountRepositoryInterface $accountRepository
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user    = JWTAuth::parseToken()->authenticate();
        $account = $this->accountRepository->findActiveByUserId($user->id);

        if (!$account) {
            return $this->noAccount();
        }

        $products = $this->productService->list($account, $request->only([
            'search', 'status', 'category_id', 'min_price', 'max_price', 'sku', 'published_on',
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Productos obtenidos.',
            'data'    => $products,
            'errors'  => [],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $product = $this->productService->find($id);

        if (!$product) {
            return response()->json(['success' => false, 'message' => 'Producto no encontrado.', 'data' => null, 'errors' => []], 404);
        }

        return response()->json(['success' => true, 'message' => 'Producto encontrado.', 'data' => $product, 'errors' => []]);
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        $this->authorize('create', \App\Models\Product::class);

        $user    = JWTAuth::parseToken()->authenticate();
        $account = $this->accountRepository->findActiveByUserId($user->id);

        if (!$account) {
            return $this->noAccount();
        }

        $imagePath = $request->hasFile('image')
            ? $request->file('image')->getPathname()
            : null;

        $product = $this->productService->create($account, ProductDTO::fromArray($request->validated()), $imagePath);

        return response()->json(['success' => true, 'message' => 'Producto creado y publicado en Mercado Libre.', 'data' => $product, 'errors' => []], 201);
    }

    public function update(UpdateProductRequest $request, int $id): JsonResponse
    {
        $user    = JWTAuth::parseToken()->authenticate();
        $account = $this->accountRepository->findActiveByUserId($user->id);
        $product = $this->productService->find($id);

        if (!$product) {
            return response()->json(['success' => false, 'message' => 'Producto no encontrado.', 'data' => null, 'errors' => []], 404);
        }

        $this->authorize('update', $product);

        $imagePath = $request->hasFile('image')
            ? $request->file('image')->getPathname()
            : null;

        $updated = $this->productService->update($account, $product, $request->validated(), $imagePath);

        return response()->json(['success' => true, 'message' => 'Producto actualizado.', 'data' => $updated, 'errors' => []]);
    }

    public function destroy(int $id): JsonResponse
    {
        $user    = JWTAuth::parseToken()->authenticate();
        $account = $this->accountRepository->findActiveByUserId($user->id);
        $product = $this->productService->find($id);

        if (!$product) {
            return response()->json(['success' => false, 'message' => 'Producto no encontrado.', 'data' => null, 'errors' => []], 404);
        }

        $this->authorize('delete', $product);

        $this->productService->delete($account, $product);

        return response()->json(['success' => true, 'message' => 'Producto eliminado.', 'data' => null, 'errors' => []]);
    }

    public function sync(int $id): JsonResponse
    {
        $user    = JWTAuth::parseToken()->authenticate();
        $account = $this->accountRepository->findActiveByUserId($user->id);
        $product = $this->productService->find($id);

        if (!$product || !$product->meli_item_id) {
            return response()->json(['success' => false, 'message' => 'Producto no encontrado o sin ID de MeLi.', 'data' => null, 'errors' => []], 404);
        }

        $this->authorize('sync', $product);

        $synced = $this->productService->syncFromMeli($account, $product);

        return response()->json(['success' => true, 'message' => 'Producto sincronizado desde Mercado Libre.', 'data' => $synced, 'errors' => []]);
    }

    private function noAccount(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'No tienes una cuenta de Mercado Libre vinculada.',
            'data'    => null,
            'errors'  => [],
        ], 422);
    }
}
