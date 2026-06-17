# Multi-tienda MercadoLibre

Un usuario puede conectar **N cuentas de MeLi Colombia** y gestionar cada una de forma independiente.

---

## Flujo de conexión OAuth

```
/accounts → "Conectar nueva tienda"
    │
    ▼
GET /meli/connect
    └─► MeliAuthController::connect()
         └─► redirect( MercadoLibreService::getAuthorizationUrl() )
              URL: https://auth.mercadolibre.com.co/authorization
                   ?response_type=code&client_id=...&redirect_uri=...
    │
    ▼  (usuario autoriza en MeLi)
    │
GET /meli/callback?code=xxxxx
    └─► MeliAuthController::callback()
         1. exchangeCodeForTokens(code)  → { access_token, refresh_token, user_id, expires_in }
         2. getUserInfo(access_token)    → { nickname, email }
         3. accountRepository->upsertTokens(userId, [...])
             → MercadolibreAccount (crea o actualiza por meli_user_id)
         4. session(['active_meli_account_id' => $account->id])
         5. redirect('/accounts') con mensaje de éxito
```

---

## Selección de tienda activa

La tienda seleccionada se guarda en sesión:
```php
session('active_meli_account_id')  // int|null
```

### Cambiar de tienda (dropdown sidebar)
```
POST /accounts/switch  { account_id: 5 }
    └─► AccountController::switch()
         1. Verifica que account_id pertenece al usuario autenticado
         2. session(['active_meli_account_id' => $account->id])
         3. redirect()->back()
```

### Fallback automático
Si `active_meli_account_id` no está en sesión (o la cuenta fue desconectada), el repositorio cae al `findActiveByUserId` (cuenta más reciente):
```php
// MercadolibreAccountRepository::findSelectedByUser()
if ($selectedId) {
    $account = Account::where('id', $selectedId)
        ->where('user_id', $userId)
        ->where('is_active', true)
        ->first();
    if ($account) return $account;
}
return $this->findActiveByUserId($userId); // fallback
```

---

## Propagación por capas

**Regla**: los Services no acceden a la sesión. Los Controllers leen la sesión y pasan el `$accountId`.

```php
// Controller
$accountId = session('active_meli_account_id');
$data = $this->dashboardService->getMetrics($userId, $accountId);

// Service (firma)
public function getMetrics(int $userId, ?int $selectedAccountId = null): array
{
    $account = $this->accountRepository->findSelectedByUser($userId, $selectedAccountId);
    if (!$account) return $this->emptyMetrics();
    // ...
}
```

### Services actualizados con multi-tienda

| Service | Métodos con `?int $selectedAccountId = null` |
|---------|---------------------------------------------|
| `DashboardService` | `getMetrics`, `getTopProducts`, `getRecentOrders`, `getAlerts`, `getDailySales`, `getMonthlyRevenue` |
| `OrderService` | `getPaginated`, `syncFromMeli` |
| `CustomerService` | `getPaginated` |
| `ReportService` | `getSalesByDateRange`, `getTopProducts`, `getTopCustomers` |
| `ProductService` | El controller resuelve la cuenta directamente |

---

## Desconectar tienda

```
DELETE /accounts/{id}/disconnect
    └─► AccountController::disconnect()
         1. accountRepository->deactivate($id, $userId) → is_active = false
         2. Si era la activa → session()->forget('active_meli_account_id')
         3. redirect('/accounts')
```
Los datos (productos, órdenes, clientes) **no se eliminan**. Solo se desactiva el acceso.

---

## Modelo MercadolibreAccount

```php
// Campos principales
'user_id'       // FK → users
'meli_user_id'  // ID único del vendedor en MeLi
'access_token'  // hidden en serialización
'refresh_token' // hidden en serialización
'expires_at'    // datetime
'nickname'      // nombre de la tienda (obtenido de /users/me al conectar)
'email'         // email de la cuenta MeLi (nullable)
'is_active'     // false = desconectada

// Método clave
$account->isTokenExpired() // true si expires_at - 5min < now()
```

---

## Refresh automático de tokens

`MercadoLibreService` llama `refreshAccessToken($account)` automáticamente antes de cada request a la API cuando `$account->isTokenExpired()` es true.  
Dispara el event `MeliTokenRefreshed` → listener `LogTokenRefresh`.  
Reintentos con backoff: 1s, 3s, 10s.

---

## Métodos del Repository

```php
interface MercadolibreAccountRepositoryInterface {
    findByUserId(int $userId): Collection             // todas las activas del usuario
    findActiveByUserId(int $userId): ?Account         // la más reciente activa
    findSelectedByUser(int $userId, ?int $selectedId) // respeta sesión, fallback a más reciente
    findByMeliUserId(string $meliUserId): ?Account
    find(int $id): ?Account
    upsertTokens(int $userId, array $data): Account   // crea o actualiza por meli_user_id
    updateTokens(Account, string, string, Carbon)     // renueva access/refresh token
    deactivate(int $id, int $userId): bool            // is_active = false
}
```

---

## Rutas de cuentas

```
GET    /accounts                     → listar tiendas (accounts.index)
POST   /accounts/switch              → cambiar tienda activa
DELETE /accounts/{id}/disconnect     → desconectar tienda
GET    /meli/connect                 → iniciar OAuth MeLi
GET    /meli/callback                → callback OAuth (lo llama MeLi)
```
