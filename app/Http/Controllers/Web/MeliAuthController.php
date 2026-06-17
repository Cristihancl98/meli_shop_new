<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Interfaces\MercadolibreAccountRepositoryInterface;
use App\Services\MercadoLibreService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MeliAuthController extends Controller
{
    public function __construct(
        private readonly MercadoLibreService $meliService,
        private readonly MercadolibreAccountRepositoryInterface $accountRepository
    ) {}

    public function connect(): RedirectResponse
    {
        return redirect($this->meliService->getAuthorizationUrl());
    }

    public function callback(Request $request): RedirectResponse
    {
        if ($request->has('error')) {
            return redirect()->route('accounts.index')
                ->with('error', 'Acceso denegado: ' . $request->input('error_description', 'Error desconocido'));
        }

        $code = $request->input('code');

        if (!$code) {
            return redirect()->route('accounts.index')
                ->with('error', 'Código de autorización no recibido.');
        }

        try {
            $tokenData = $this->meliService->exchangeCodeForTokens($code);
            $meliUser  = $this->meliService->getUserInfo($tokenData['access_token']);

            $account = $this->accountRepository->upsertTokens(auth()->id(), [
                'meli_user_id'  => (string) $tokenData['user_id'],
                'access_token'  => $tokenData['access_token'],
                'refresh_token' => $tokenData['refresh_token'],
                'expires_at'    => Carbon::now()->addSeconds($tokenData['expires_in']),
                'nickname'      => $meliUser['nickname'] ?? 'Tienda ' . $tokenData['user_id'],
                'email'         => $meliUser['email'] ?? null,
                'is_active'     => true,
            ]);

            // Auto-seleccionar la cuenta recién conectada
            session(['active_meli_account_id' => $account->id]);

            return redirect()->route('accounts.index')
                ->with('success', '¡Tienda "' . $account->nickname . '" conectada exitosamente!');
        } catch (\Throwable $e) {
            return redirect()->route('accounts.index')
                ->with('error', 'Error al conectar: ' . $e->getMessage());
        }
    }
}
