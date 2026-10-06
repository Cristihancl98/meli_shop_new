<?php

namespace App\Http\Controllers\Api;

use App\Enums\SettingKey;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateSettingsRequest;
use App\Services\SettingService;
use App\Traits\ApiResponses;
use App\Traits\ResolvesMeliAccount;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class SettingController extends Controller
{
    use ApiResponses, ResolvesMeliAccount;

    public function __construct(private readonly SettingService $settingService) {}

    public function index(): JsonResponse
    {
        Gate::authorize('view-settings');

        return $this->ok($this->settingService->pricingSettings(), 'Configuración obtenida.');
    }

    public function reputation(): JsonResponse
    {
        Gate::authorize('view-settings');

        if (!$account = $this->currentAccount()) {
            return $this->noAccount();
        }

        return $this->ok($this->settingService->reputation($account), 'Reputación obtenida.');
    }

    public function descriptionTemplate(): JsonResponse
    {
        Gate::authorize('view-settings');

        return $this->ok(
            ['description_template' => $this->settingService->get(SettingKey::DescriptionTemplate)],
            'Plantilla de descripción obtenida.'
        );
    }

    public function update(UpdateSettingsRequest $request): JsonResponse
    {
        Gate::authorize('update-settings');

        return $this->ok($this->settingService->update($request->validated()), 'Configuración guardada correctamente.');
    }
}
