<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Interfaces\MercadolibreAccountRepositoryInterface;
use App\Services\MercadoLibreService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

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
            return redirect('/dashboard?meli_error=' . $request->input('error_description', 'Acceso denegado'));
        }

        $code = $request->input('code');

        if (!$code) {
            return redirect('/dashboard?meli_error=Código de autorización no recibido');
        }

        try {
            $tokenData = $this->meliService->exchangeCodeForTokens($code);

            $user = JWTAuth::parseToken()->authenticate();

            $this->accountRepository->upsertTokens($user->id, [
                'meli_user_id'  => (string) $tokenData['user_id'],
                'access_token'  => $tokenData['access_token'],
                'refresh_token' => $tokenData['refresh_token'],
                'expires_at'    => Carbon::now()->addSeconds($tokenData['expires_in']),
                'is_active'     => true,
            ]);

            return redirect('/dashboard?meli_connected=1');
        } catch (\Throwable $e) {
            return redirect('/dashboard?meli_error=' . urlencode($e->getMessage()));
        }
    }
}
