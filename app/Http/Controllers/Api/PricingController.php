<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pricing\CalculatePriceRequest;
use App\Services\PricingService;
use App\Traits\ApiResponses;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class PricingController extends Controller
{
    use ApiResponses;

    public function __construct(private readonly PricingService $pricingService) {}

    public function calculate(CalculatePriceRequest $request): JsonResponse
    {
        Gate::authorize('view-settings');

        $breakdown = $this->pricingService->calculate(
            (float) $request->validated('base_price'),
            (float) $request->validated('weight')
        );

        return $this->ok($breakdown->toArray(), 'Precio calculado.');
    }
}
