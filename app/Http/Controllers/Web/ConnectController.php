<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Store\ConnectStoreRequest;
use App\Services\TenantManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ConnectController extends Controller
{
    public function __construct(private readonly TenantManager $tenantManager) {}

    public function show(): View|RedirectResponse
    {
        if ($this->tenantManager->current()) {
            return redirect()->route('login');
        }

        return view('auth.connect');
    }

    public function store(ConnectStoreRequest $request): RedirectResponse
    {
        $store = $this->tenantManager->activateByCode($request->validated('connection_code'));

        if (!$store) {
            return back()
                ->withErrors(['connection_code' => 'Código de conexión inválido o tienda deshabilitada.'])
                ->withInput();
        }

        $request->session()->regenerate();
        $request->session()->put(config('tenancy.session_key'), $store->id);

        return redirect()->route('login');
    }

    public function change(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('connect.show');
    }
}
