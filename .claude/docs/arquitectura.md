# Arquitectura

## Capas y flujo de una request

```
HTTP Request
    │
    ▼
Middleware (WebAuthMiddleware | JwtMiddleware | RoleMiddleware)
    │
    ▼
Controller  ← solo recibe request, llama Service, devuelve View/JSON
    │         usa FormRequest para validar entrada
    ▼
Service     ← TODA la lógica de negocio
    │         depende de Repositories via Interface
    │         dispara Events
    ▼
Repository  ← solo Eloquent/queries, implementa Interface
    │
    ▼
Model       ← relaciones, casts, scopes
```

---

## Estructura de carpetas relevante

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Api/          ← devuelven JSON, protegidos con JWT
│   │   └── Web/          ← devuelven Blade views, protegidos con sesión
│   ├── Middleware/
│   │   ├── JwtMiddleware.php        ← rutas /api/*
│   │   ├── WebAuthMiddleware.php    ← rutas web/*
│   │   └── RoleMiddleware.php
│   └── Requests/         ← TODA validación aquí, nunca en Service
├── Services/             ← TODA la lógica de negocio
├── Repositories/         ← implementaciones de Interfaces
├── Interfaces/           ← una por Repository
├── Models/
├── DTOs/                 ← objetos readonly entre capas, sin arrays crudos
├── Policies/             ← autorización por rol
├── Jobs/                 ← tareas > 200ms en cola (driver: database)
├── Events/ + Listeners/
└── Providers/
    └── AppServiceProvider.php ← bind Interface→Repo + Gates
```

---

## Reglas obligatorias

1. **Controllers** → solo llaman Services. Cero lógica de negocio.
2. **Services** → toda la lógica. Dependen de Repositories via Interface, nunca directamente.
3. **Repositories** → solo Eloquent/queries. Implementan Interface.
4. **Interfaces** → una por Repository. Registrar en `AppServiceProvider::register()`.
5. **DTOs** → objetos inmutables (`readonly`) para pasar datos entre capas.
6. **Form Requests** → toda validación de entrada. Nunca validar en Service.
7. **Policies** → toda autorización. Sin if/else de rol en Controllers o Services.
8. **Jobs** → toda tarea > 200ms. Queue driver: `database`.

---

## Autorización

### Policies con modelo (auto-descubiertas)
```php
// Controller
$this->authorize('viewAny', Order::class);  // → OrderPolicy::viewAny()
$this->authorize('view', $order);           // → OrderPolicy::view()
```
Laravel descubre automáticamente `App\Policies\{Model}Policy`.

### Policies SIN modelo → registrar como Gates
Para resources sin modelo (ej: `ReportPolicy`), registrar en `AppServiceProvider::boot()`:
```php
Gate::define('view-report',   [ReportPolicy::class, 'view']);
Gate::define('export-report', [ReportPolicy::class, 'export']);
```
Usar en controller sin segundo argumento:
```php
$this->authorize('view-report');
```
En Blade:
```blade
@can('export-report') ... @endcan
```
**NUNCA** usar `@can('export', ReportPolicy::class)` — ReportPolicy no es un modelo.

### Base Controller
`Controller.php` incluye el trait necesario para `$this->authorize()`:
```php
abstract class Controller
{
    use AuthorizesRequests;
}
```

---

## Respuesta API estándar

```json
{
  "success": true,
  "data": {},
  "message": "Descripción",
  "errors": {}
}
```

---

## Roles y permisos

| Acción | Admin | Operador |
|--------|-------|----------|
| Ver productos/ventas/clientes | ✓ | ✓ |
| Crear/editar/eliminar productos | ✓ | ✗ |
| Ver reportes | ✓ | ✓ |
| Exportar reportes Excel | ✓ | ✗ |
| Sincronizar ventas | ✓ | ✗ |

Verificación: `$user->isAdmin()` / `$user->isOperator()` en `User` model.

---

## Notas importantes

- `decimal(12,2)` para todos los valores monetarios en COP.
- Soft deletes en `products`, `customers`, `orders`.
- Paginación: 20 registros por página en todos los listados.
- `QUEUE_CONNECTION=database`. No Redis.
- `es_CO` para formatos de fecha. Moneda COP sin símbolo `$`.
