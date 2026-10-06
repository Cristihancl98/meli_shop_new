<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateSettingsRequest;
use App\Services\SettingService;
use App\Traits\ResolvesMeliAccount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class SettingController extends Controller
{
    use ResolvesMeliAccount;

    public function __construct(private readonly SettingService $settingService) {}

    public function edit(): View
    {
        Gate::authorize('view-settings');

        $account    = $this->currentAccount();
        $settings   = $this->settingService->pricingSettings();
        $reputation = $this->settingService->reputation($account);
        $canEdit    = Gate::allows('update-settings');

        return view('settings.edit', compact('account', 'settings', 'reputation', 'canEdit'));
    }

    public function update(UpdateSettingsRequest $request): RedirectResponse
    {
        Gate::authorize('update-settings');

        $this->settingService->update($request->validated());

        return redirect()->route('settings.edit')->with('success', 'Configuración guardada.');
    }
}
