# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

# Marketplace Manager — Mercado Libre Colombia

## Descripción del Proyecto

Panel administrativo para gestionar publicaciones, ventas, clientes y métricas de Mercado Libre Colombia (MCO) desde una sola interfaz.

Es una **migración en curso desde una app CodeIgniter**: los comentarios `migrado de Gestion / Inicio / Configuraciones / Precio` en `routes/` señalan los módulos portados, y sus tests viven en `tests/Feature/Migrated/`. Documentación extendida en `.claude/docs/` (arquitectura, autenticación, multi-tienda, rutas, base de datos, instalación).

---

## Multi-tienda (tenancy) — leer antes de tocar BD, jobs o comandos

Hay **dos niveles** de multi-tenencia:

1. **Tienda (`Store`) = una base de datos MySQL propia.** La BD central (`landlord`, p. ej. `meli_central`) solo guarda `stores`, `store_meli_accounts`, sessions, cache y jobs. Todo lo demás (users, products, orders…) vive en la BD de cada tienda (`meli_store_<slug>`), conexión `tenant`, que es la conexión por defecto (`DB_CONNECTION=tenant`) y cuyo `database` es `null` hasta que se activa una tienda.
2. **Cuenta MeLi (`MercadolibreAccount`) dentro de la tienda.** Un usuario vincula N cuentas; la activa se toma de `session('active_meli_account_id')` (web) o del header `X-Meli-Account` (API) vía el trait `ResolvesMeliAccount`.

`App\Services\TenantManager` (singleton) cambia la conexión `tenant` (`config(...)` + `DB::purge('tenant')`). Puntos de activación:
- **Web**: `IdentifyTenantFromSession` (añadido al grupo `web`) lee `session('store_id')`; sin tienda redirige a `/connect`, donde se ingresa el *código de conexión*.
- **API login/register**: middleware `tenant.code` (header `X-Store-Code` o `connection_code`).
- **API autenticada**: `JwtMiddleware` lee el claim `store` del JWT y activa la tienda antes de `JWTAuth::authenticate()`.
- **Webhook**: `WebhookController` resuelve la tienda por `meli_user_id` (`activateByMeliUserId`, usando `store_meli_accounts` en landlord).
- **Jobs**: `AppServiceProvider::makeQueueTenantAware()` agrega `store_id` al payload de cada job y reactiva la tienda en `JobProcessing`. Despachar siempre con una tienda activa.
- **Comandos de sync**: heredan de `AccountJobCommand`, que itera `TenantManager::eachActiveStore()` y cada cuenta activa; aceptan `--store=` y `--account=`.

Modelos landlord (`Store`, `StoreMeliAccount`) declaran `protected $connection = 'landlord'`. Las migraciones de tienda van en `database/migrations/tenant/` (no en `database/migrations/`).

Settings: `App\Enums\SettingKey` define cada clave; `isAccountScoped()` decide si se guarda por cuenta (`AccountSettingRepository`) o por tienda (`StoreSettingRepository`). Valores por defecto en `config/store_defaults.php`.

Catálogo externo: `ExternalCatalogRepositoryInterface` → `MongoCatalogRepository` (conexión `mongodb`, `MONGODB_URI`). En tests se reemplaza por `tests/Fakes/FakeCatalogRepository`.

---

## Stack Tecnológico

| Capa | Tecnología | Versión |
|------|-----------|---------|
| Framework | Laravel | 12.x (compatible con L13 API) |
| PHP | PHP | 8.2+ |
| Base de datos | MySQL | 8.x |
| Auth | tymon/jwt-auth | latest |
| Queue | Laravel Queue | driver: database |
| Export | maatwebsite/excel | latest |
| Docs API | darkaonline/l5-swagger | latest |
| Storage | AWS S3 (league/flysystem-aws-s3-v3) | latest |
| Frontend | Bootstrap | 5.3 |
| Gráficos | Chart.js | latest CDN |
| Íconos | Bootstrap Icons | latest CDN |

**NO usar:** Livewire, Vue, React, DataTables. Solo Blade + Bootstrap puro.

---

## Arquitectura de Carpetas

```
app/
  Http/
    Controllers/      ← Solo reciben requests y devuelven responses
    Middleware/        ← Auth JWT, roles, permisos
    Requests/          ← Form Requests para toda validación
  Services/            ← TODA la lógica de negocio
  Repositories/        ← TODO acceso a base de datos
  Interfaces/          ← Interface de cada Repository
  Models/
  Jobs/                ← Tareas pesadas en background
  Events/
  Listeners/
  DTOs/                ← Transferencia de datos entre capas
  Policies/            ← Autorización por rol
  Traits/
  Exceptions/          ← Exception handling centralizado
```

---

## Reglas de Arquitectura (OBLIGATORIAS)

1. **Controllers** → Solo llaman Services. Cero lógica de negocio.
2. **Services** → Toda la lógica. Dependen de Repositories via Interface.
3. **Repositories** → Solo Eloquent/queries. Implementan una Interface.
4. **Interfaces** → Una por cada Repository. Siempre inyectar la Interface, no la implementación.
5. **DTOs** → Objetos inmutables para pasar datos entre capas. No usar arrays crudos.
6. **Form Requests** → Toda validación de entrada en Controllers. Nunca validar en Service.
7. **Policies** → Toda autorización. Nunca if/else de rol en Controllers o Services.
8. **Jobs** → Toda tarea que tome más de 200ms. Queue driver: database.

---

## Principios de Código

- **SOLID** obligatorio en toda clase nueva.
- **Sin comentarios innecesarios** — nombres claros son suficientes.
- **Respuesta API consistente** siempre con esta estructura:
  ```json
  { "success": true, "data": {}, "message": "...", "errors": {} }
  ```
- **Sin lógica en Blade** — solo presentación. Variables simples desde el Controller.
- **Soft deletes** en: products, customers, orders.
- **Moneda**: Todos los valores monetarios en COP (pesos colombianos). Decimales: 2.

---

## Integración Mercado Libre Colombia

- **País**: MCO (Colombia)
- **Auth URL**: `https://auth.mercadolibre.com.co/authorization`
- **API Base**: `https://api.mercadolibre.com`
- **Multi-cuenta**: Un usuario puede vincular N cuentas MeLi. Todos los modelos tienen `mercadolibre_account_id`.
- **Token refresh**: Auto-refresh cuando `expires_at` < 5 minutos.
- **Rate limiting**: Exponential backoff — 3 reintentos: 1s, 3s, 10s.
- **Webhook**: `POST /api/webhooks/mercadolibre` — responder HTTP 200 inmediato, procesar en Job.

---

## Base de Datos

**Conexión**: MySQL 8 (NO SQLite en producción)

### Orden de migraciones de tienda (`database/migrations/tenant/`, respetar FK dependencies):
1. `users`
2. `roles`
3. `user_roles`
4. `mercadolibre_accounts`
5. `categories`
6. `products`
7. `customers`
8. `orders`
9. `order_items`
10. `product_statistics`
11. `sales_statistics`
12. `sync_logs`

### Campos especiales:
- `price`, `total_amount`, `unit_price`, `total_price`, `total_sales`, `total_revenue`, `average_ticket` → `decimal(12,2)`
- `status` en products → enum('active', 'paused', 'closed')
- `status` en sync_logs → enum('pending', 'running', 'success', 'error')
- `type` en sync_logs → enum('products', 'orders', 'customers', 'statistics', 'webhook')

---

## Comandos Artisan del Proyecto

```bash
# Sincronización (corren via Scheduler)
php artisan sync:products       # cada 30 min
php artisan sync:orders         # cada 10 min
php artisan sync:customers      # cada hora
php artisan calculate:statistics # cada hora

# Además: meli:refresh-tokens, sync:questions, sync:post-sale-messages,
# sync:product-statuses, sync:reputation, sync:dollar (ver routes/console.php)
# Todos aceptan --store=<id> (y la mayoría --account=<id>)

# Setup inicial (multi-tienda)
php artisan jwt:secret
php artisan tenancy:install                 # migraciones de la BD central (landlord)
php artisan store:create "Mi Tienda" --admin-email=admin@x.com   # crea BD, migra, siembra roles/categorías y admin
php artisan tenants:migrate [--store=ID] [--fresh --force]       # migra database/migrations/tenant en cada tienda
php artisan settings:defaults [--store=ID]
php artisan storage:link
php artisan queue:work --queue=default

# Docker (app + nginx + mysql; workers con perfil)
docker compose up -d                        # entrypoint corre tenancy:install y tenants:migrate
docker compose --profile workers up -d      # + queue y scheduler
# App en :8000, MySQL expuesto en :3307. docker/mysql/init otorga permisos para crear BDs meli_store_*

# Desarrollo
composer dev        # serve + queue:listen + pail + vite en paralelo
composer setup      # instalación inicial completa
./vendor/bin/pint   # formateo/lint (Laravel Pint)
```

---

## Comandos de Desarrollo Frecuentes

```bash
# Instalar dependencias del proyecto
composer require tymon/jwt-auth darkaonline/l5-swagger maatwebsite/excel league/flysystem-aws-s3-v3

# Publicar configuraciones
php artisan vendor:publish --provider="Tymon\JWTAuth\Providers\LaravelServiceProvider"
php artisan vendor:publish --provider="L5Swagger\L5SwaggerServiceProvider"

# Tests
composer test                                   # config:clear + php artisan test
php artisan test --coverage --min=80
php artisan test --filter=TenancyTest           # una clase
php artisan test --filter=test_valid_code_selects_store_and_shows_login
php artisan test tests/Feature/Migrated/PricingTest.php
```

---

## Roles y Permisos

| Acción | Admin | Operador |
|--------|-------|----------|
| Crear/Editar/Eliminar productos | ✓ | ✗ |
| Ver productos | ✓ | ✓ |
| Ver ventas | ✓ | ✓ |
| Ver clientes | ✓ | ✓ |
| Exportar reportes | ✓ | ✗ |

---

## Almacenamiento

- **Imágenes de productos**: AWS S3 (`FILESYSTEM_DISK=s3`)
- **Subir a MeLi también**: via `POST /pictures/items/upload`
- **Guardar en DB**: URL pública S3 en campo `thumbnail`

---

## API REST — Endpoints Principales

```
POST   /api/auth/login
POST   /api/auth/register
POST   /api/auth/logout
POST   /api/auth/refresh
GET    /api/auth/profile
PUT    /api/auth/profile

GET    /meli/connect
GET    /meli/callback
POST   /api/webhooks/mercadolibre

GET    /api/dashboard
GET    /api/dashboard/top-products
GET    /api/dashboard/sales-summary
GET    /api/dashboard/alerts

GET    /api/products
GET    /api/products/{id}
POST   /api/products
PUT    /api/products/{id}
DELETE /api/products/{id}
POST   /api/products/{id}/sync

GET    /api/orders
GET    /api/orders/{id}

GET    /api/customers
GET    /api/customers/{id}
GET    /api/customers/{id}/orders

GET    /api/reports/sales
GET    /api/reports/top-products
GET    /api/reports/top-customers
GET    /api/reports/export/products
GET    /api/reports/export/sales
```

---

## Events y Listeners Registrados

| Event | Listener | Acción |
|-------|----------|--------|
| `ProductSynced` | `UpdateProductStatistics` | Actualiza product_statistics |
| `OrderSynced` | `UpdateSalesStatistics` | Actualiza sales_statistics y product_statistics |
| `CustomerSynced` | `LogCustomerActivity` | Log de actividad |
| `MeliTokenRefreshed` | `LogTokenRefresh` | Log de renovación |
| `SyncFailed` | `NotifyAdminOfFailure` | Email al admin |

---

## Testing

- **Framework**: PHPUnit
- **Cobertura mínima**: 80%
- **Regla**: Mockear siempre la API de Mercado Libre. Nunca hacer calls reales en tests.
- **Factories**: Usar factories para todos los datos de prueba. Nunca hardcodear.
- **BD de tests**: `tests/TestCase.php` redirige `tenant` y `landlord` a **un mismo SQLite en memoria** (comparten PDO), desactiva `tenancy.switch_connection` y migra ambas rutas (`database/migrations` + `database/migrations/tenant`). Por eso las migraciones deben ser compatibles con SQLite aunque producción sea MySQL. Queue en `sync`.
- `setUp()` ya crea y activa una tienda (`TEST-CODE`), la pone en sesión, siembra roles y registra el catálogo fake. Usar los helpers `createAdmin()`, `createOperator()`, `adminWithAccount()`, `operatorWithAccount()`, `setSettings()` y `authHeaders($user)` (JWT).
- Mockear MeLi con `Http::fake()`.

---

## Notas Importantes

- **Laravel 12 vs 13**: El proyecto corre Laravel 12.x. La API es idéntica a L13 — no requiere cambios.
- **No SQLite en runtime**: MySQL 8 es requerido (cada tienda es una BD MySQL creada por `store:create`). SQLite solo se usa en memoria en los tests.
- **Queue**: Siempre `QUEUE_CONNECTION=database`. No Redis.
- **Pagination**: 20 registros por página en todos los listados.
- **Locale**: `es_CO` para formatos de fecha. Moneda COP sin símbolo de dólar.
