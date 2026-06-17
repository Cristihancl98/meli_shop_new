# Marketplace Manager — Documentación

Panel administrativo multi-tienda para gestionar publicaciones, ventas, clientes y métricas de **Mercado Libre Colombia (MCO)**.

---

## Índice

| Documento | Contenido |
|-----------|-----------|
| [arquitectura.md](arquitectura.md) | Capas, patrones, reglas obligatorias |
| [autenticacion.md](autenticacion.md) | Web (sesión) vs API (JWT), flujos completos |
| [multi-tienda.md](multi-tienda.md) | Conectar/cambiar/desconectar tiendas MeLi |
| [rutas.md](rutas.md) | Todas las rutas web y API con sus middlewares |
| [base-de-datos.md](base-de-datos.md) | Esquema completo, relaciones, convenciones |
| [instalacion.md](instalacion.md) | Setup local paso a paso |

---

## Stack

| Capa | Tecnología |
|------|-----------|
| Framework | Laravel 12.x / PHP 8.2+ |
| Base de datos | MySQL 8.x |
| Auth web | Laravel Session (guard `web`) |
| Auth API | JWT — `tymon/jwt-auth` |
| Queue | Laravel Queue, driver `database` |
| Storage | AWS S3 |
| Export | `maatwebsite/excel` |
| Frontend | Blade + Bootstrap 5.3 + Chart.js + Bootstrap Icons |

**NO usar:** Livewire, Vue, React, DataTables. Solo Blade + Bootstrap puro.

---

## Comandos frecuentes

```bash
# Desarrollo
php artisan serve
php artisan queue:listen

# Setup inicial
php artisan migrate --seed
php artisan jwt:secret
php artisan storage:link

# Limpiar cachés (siempre después de cambios en rutas/config)
php artisan route:clear && php artisan config:clear && php artisan cache:clear && php artisan view:clear

# Sincronización manual
php artisan sync:products
php artisan sync:orders
php artisan sync:customers
php artisan calculate:statistics

# Tests
php artisan test --coverage --min=80
```
