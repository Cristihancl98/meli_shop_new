<?php

namespace App\Http\Middleware;

use App\Services\TenantManager;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IdentifyTenantFromSession
{
    private const PUBLIC_ROUTES = ['connect.show', 'connect.store'];

    public function __construct(private readonly TenantManager $tenantManager) {}

    public function handle(Request $request, Closure $next): Response
    {
        $sessionKey = config('tenancy.session_key');

        if ($store = $this->tenantManager->activateById($request->session()->get($sessionKey))) {
            view()->share('currentStore', $store);

            return $next($request);
        }

        $request->session()->forget($sessionKey);

        if ($request->routeIs(...self::PUBLIC_ROUTES)) {
            return $next($request);
        }

        return redirect()->route('connect.show');
    }
}
