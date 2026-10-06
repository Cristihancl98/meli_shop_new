<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Meli\PredictCategoryRequest;
use App\Models\Product;
use App\Services\MercadoLibreService;
use App\Traits\ApiResponses;
use App\Traits\ResolvesMeliAccount;
use Illuminate\Http\JsonResponse;

class MeliCategoryController extends Controller
{
    use ApiResponses, ResolvesMeliAccount;

    public function __construct(private readonly MercadoLibreService $meliService) {}

    public function index(): JsonResponse
    {
        $this->authorize('viewAny', Product::class);

        if (!$account = $this->currentAccount()) {
            return $this->noAccount();
        }

        return $this->ok($this->meliService->getSiteCategories($account), 'Categorías obtenidas.');
    }

    public function show(string $categoryId): JsonResponse
    {
        $this->authorize('viewAny', Product::class);

        if (!$account = $this->currentAccount()) {
            return $this->noAccount();
        }

        return $this->ok($this->meliService->getCategory($account, $categoryId), 'Categoría obtenida.');
    }

    public function predict(PredictCategoryRequest $request): JsonResponse
    {
        $this->authorize('viewAny', Product::class);

        if (!$account = $this->currentAccount()) {
            return $this->noAccount();
        }

        return $this->ok($this->meliService->predictCategory($account, $request->validated('title')), 'Predicción obtenida.');
    }
}
