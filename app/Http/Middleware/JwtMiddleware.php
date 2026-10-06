<?php

namespace App\Http\Middleware;

use App\Services\TenantManager;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Facades\JWTAuth;

class JwtMiddleware
{
    public function __construct(private readonly TenantManager $tenantManager) {}

    public function handle(Request $request, Closure $next): Response
    {
        $isApi = $request->expectsJson() || str_starts_with($request->path(), 'api/');

        \Illuminate\Support\Facades\Log::info('JwtMiddleware', [
            'path'   => $request->path(),
            'isApi'  => $isApi,
            'cookie' => $request->hasCookie('jwt_token'),
            'accept' => $request->header('Accept'),
        ]);

        if (!$isApi && $request->hasCookie('jwt_token')) {
            $request->headers->set('Authorization', 'Bearer ' . $request->cookie('jwt_token'));
        }

        try {
            $storeId = JWTAuth::parseToken()->getPayload()->get(config('tenancy.jwt_claim'));

            if (!$this->tenantManager->activateById($storeId ? (int) $storeId : null)) {
                throw new TokenInvalidException('Token sin tienda válida.');
            }

            JWTAuth::authenticate();
        } catch (TokenExpiredException) {
            \Illuminate\Support\Facades\Log::info('JwtMiddleware: TokenExpiredException, isApi=' . ($isApi ? 'true' : 'false'));
            if ($isApi) {
                return response()->json(['success' => false, 'message' => 'Token expirado.', 'data' => null, 'errors' => []], 401);
            }
            return redirect('/login')->withErrors(['email' => 'Sesión expirada. Inicia sesión nuevamente.']);
        } catch (TokenInvalidException) {
            \Illuminate\Support\Facades\Log::info('JwtMiddleware: TokenInvalidException, isApi=' . ($isApi ? 'true' : 'false'));
            if ($isApi) {
                return response()->json(['success' => false, 'message' => 'Token inválido.', 'data' => null, 'errors' => []], 401);
            }
            return redirect('/login')->withErrors(['email' => 'Sesión inválida. Inicia sesión nuevamente.']);
        } catch (JWTException) {
            \Illuminate\Support\Facades\Log::info('JwtMiddleware: JWTException, isApi=' . ($isApi ? 'true' : 'false'));
            if ($isApi) {
                return response()->json(['success' => false, 'message' => 'Token no proporcionado.', 'data' => null, 'errors' => []], 401);
            }
            return redirect('/login');
        }

        return $next($request);
    }
}
