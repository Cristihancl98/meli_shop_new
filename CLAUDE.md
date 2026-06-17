# Marketplace Manager — Mercado Libre Colombia

## Descripción del Proyecto

Panel administrativo para gestionar publicaciones, ventas, clientes y métricas de Mercado Libre Colombia (MCO) desde una sola interfaz.

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

### Orden de migraciones (respetar FK dependencies):
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

# Setup inicial
php artisan migrate --seed
php artisan jwt:secret
php artisan storage:link
php artisan queue:work --queue=default

# Desarrollo
php artisan serve
php artisan queue:listen
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
php artisan test
php artisan test --coverage --min=80
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

---

## Notas Importantes

- **Laravel 12 vs 13**: El proyecto corre Laravel 12.x. La API es idéntica a L13 — no requiere cambios.
- **No SQLite**: El `.env` local tiene SQLite por defecto pero MySQL 8 es requerido para producción.
- **Queue**: Siempre `QUEUE_CONNECTION=database`. No Redis.
- **Pagination**: 20 registros por página en todos los listados.
- **Locale**: `es_CO` para formatos de fecha. Moneda COP sin símbolo de dólar.
