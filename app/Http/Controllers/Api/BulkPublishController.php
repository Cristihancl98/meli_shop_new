<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\BulkPublish\ListBulkCodesRequest;
use App\Http\Requests\BulkPublish\StoreBulkCodesRequest;
use App\Models\Product;
use App\Services\BulkPublishService;
use App\Traits\ApiResponses;
use App\Traits\ResolvesMeliAccount;
use Illuminate\Http\JsonResponse;

class BulkPublishController extends Controller
{
    use ApiResponses, ResolvesMeliAccount;

    public function __construct(private readonly BulkPublishService $bulkPublishService) {}

    public function index(ListBulkCodesRequest $request): JsonResponse
    {
        $this->authorize('publish', Product::class);

        if (!$account = $this->currentAccount()) {
            return $this->noAccount();
        }

        return $this->ok($this->bulkPublishService->list($account, $request->validated('date')), 'Códigos obtenidos.');
    }

    public function store(StoreBulkCodesRequest $request): JsonResponse
    {
        $this->authorize('publish', Product::class);

        if (!$account = $this->currentAccount()) {
            return $this->noAccount();
        }

        $count = $this->bulkPublishService->addCodes($account, $request->validated('skus'));

        return $this->ok(['registered' => $count], 'Códigos registrados.', 201);
    }
}
