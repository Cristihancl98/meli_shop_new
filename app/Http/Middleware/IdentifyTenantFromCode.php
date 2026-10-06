<?php

namespace App\Http\Middleware;

use App\Services\TenantManager;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IdentifyTenantFromCode
{
    public function __construct(private readonly TenantManager $tenantManager) {}

    public function handle(Request $request, Closure $next): Response
    {
        $code = (string) ($request->header('X-Store-Code') ?? $request->input('connection_code', ''));

        if ($code === '' || !$this->tenantManager->activateByCode($code)) {
            return response()->json([
                'success' => false,
                'data'    => null,
                'message' => 'Código de conexión inválido o tienda deshabilitada.',
                'errors'  => ['connection_code' => ['Código de conexión inválido o tienda deshabilitada.']],
            ], 422);
        }

        return $next($request);
    }
}
