# Rutas del Proyecto

---

## Rutas Web (`routes/web.php`) — Middleware `web.auth`

### Públicas (sin autenticación)
| Método | URI | Controlador | Nombre |
|--------|-----|-------------|--------|
| GET | `/login` | `Web\AuthController@showLogin` | `login` |
| POST | `/login` | `Web\AuthController@login` | — |
| POST | `/logout` | `Web\AuthController@logout` | `logout` |

### Protegidas (middleware `web.auth` = sesión Laravel)

#### MercadoLibre OAuth
| Método | URI | Controlador | Nombre |
|--------|-----|-------------|--------|
| GET | `/meli/connect` | `Web\MeliAuthController@connect` | `meli.connect` |
| GET | `/meli/callback` | `Web\MeliAuthController@callback` | `meli.callback` |

#### Cuentas multi-tienda
| Método | URI | Controlador | Nombre |
|--------|-----|-------------|--------|
| GET | `/accounts` | `Web\AccountController@index` | `accounts.index` |
| POST | `/accounts/switch` | `Web\AccountController@switch` | `accounts.switch` |
| DELETE | `/accounts/{id}/disconnect` | `Web\AccountController@disconnect` | `accounts.disconnect` |

#### Dashboard
| Método | URI | Controlador | Nombre |
|--------|-----|-------------|--------|
| GET | `/dashboard` | `Web\DashboardController@index` | `dashboard` |

#### Productos
| Método | URI | Controlador | Nombre |
|--------|-----|-------------|--------|
| GET | `/products` | `Web\ProductController@index` | `products.index` |
| GET | `/products/create` | `Web\ProductController@create` | `products.create` |
| POST | `/products` | `Web\ProductController@store` | `products.store` |
| GET | `/products/{id}` | `Web\ProductController@show` | `products.show` |
| GET | `/products/{id}/edit` | `Web\ProductController@edit` | `products.edit` |
| PUT/PATCH | `/products/{id}` | `Web\ProductController@update` | `products.update` |
| DELETE | `/products/{id}` | `Web\ProductController@destroy` | `products.destroy` |
| POST | `/products/{id}/sync` | `Web\ProductController@sync` | `products.sync` |

#### Ventas
| Método | URI | Controlador | Nombre |
|--------|-----|-------------|--------|
| GET | `/orders` | `Web\OrderController@index` | `orders.index` |
| GET | `/orders/{id}` | `Web\OrderController@show` | `orders.show` |
| POST | `/orders/sync` | `Web\OrderController@sync` | `orders.sync` |

#### Clientes
| Método | URI | Controlador | Nombre |
|--------|-----|-------------|--------|
| GET | `/customers` | `Web\CustomerController@index` | `customers.index` |
| GET | `/customers/{id}` | `Web\CustomerController@show` | `customers.show` |

#### Reportes
| Método | URI | Controlador | Nombre |
|--------|-----|-------------|--------|
| GET | `/reports/sales` | `Web\ReportController@sales` | `reports.sales` |
| GET | `/reports/products` | `Web\ReportController@products` | `reports.products` |
| GET | `/reports/customers` | `Web\ReportController@customers` | `reports.customers` |
| GET | `/reports/export/sales` | `Web\ReportController@exportSales` | `reports.export.sales` |
| GET | `/reports/export/products` | `Web\ReportController@exportProducts` | `reports.export.products` |
| GET | `/reports/export/customers` | `Web\ReportController@exportCustomers` | `reports.export.customers` |

---

## Rutas API (`routes/api.php`) — Prefijo `/api/`, Middleware `jwt.auth`

### Públicas (sin JWT)
| Método | URI | Controlador |
|--------|-----|-------------|
| POST | `/api/auth/login` | `Api\AuthController@login` |
| POST | `/api/auth/register` | `Api\AuthController@register` |
| POST | `/api/auth/forgot-password` | `Api\AuthController@forgotPassword` |
| POST | `/api/auth/reset-password` | `Api\AuthController@resetPassword` |
| POST | `/api/webhooks/mercadolibre` | `Api\WebhookController@handle` |

### Protegidas (JWT requerido)
| Método | URI | Controlador | Nombre |
|--------|-----|-------------|--------|
| POST | `/api/auth/logout` | `Api\AuthController@logout` | — |
| POST | `/api/auth/refresh` | `Api\AuthController@refresh` | — |
| GET | `/api/auth/profile` | `Api\AuthController@profile` | — |
| PUT | `/api/auth/profile` | `Api\ProfileController@update` | — |
| GET | `/api/dashboard` | `Api\DashboardController@index` | `api.dashboard` |
| GET | `/api/dashboard/top-products` | `Api\DashboardController@topProducts` | `api.dashboard.top-products` |
| GET | `/api/dashboard/sales-summary` | `Api\DashboardController@salesSummary` | `api.dashboard.sales-summary` |
| GET | `/api/dashboard/alerts` | `Api\DashboardController@alerts` | `api.dashboard.alerts` |
| GET | `/api/products` | `Api\ProductController@index` | `api.products.index` |
| POST | `/api/products` | `Api\ProductController@store` | `api.products.store` |
| GET | `/api/products/{id}` | `Api\ProductController@show` | `api.products.show` |
| PUT | `/api/products/{id}` | `Api\ProductController@update` | `api.products.update` |
| DELETE | `/api/products/{id}` | `Api\ProductController@destroy` | `api.products.destroy` |
| POST | `/api/products/{id}/sync` | `Api\ProductController@sync` | `api.products.sync` |
| GET | `/api/orders` | `Api\OrderController@index` | `api.orders.index` |
| GET | `/api/orders/{id}` | `Api\OrderController@show` | `api.orders.show` |
| POST | `/api/orders/sync` | `Api\OrderController@sync` | `api.orders.sync` |
| GET | `/api/customers` | `Api\CustomerController@index` | `api.customers.index` |
| GET | `/api/customers/{id}` | `Api\CustomerController@show` | `api.customers.show` |
| GET | `/api/customers/{id}/orders` | `Api\CustomerController@orders` | `api.customers.orders` |
| GET | `/api/reports/sales` | `Api\ReportController@sales` | `api.reports.sales` |
| GET | `/api/reports/top-products` | `Api\ReportController@topProducts` | `api.reports.top-products` |
| GET | `/api/reports/top-customers` | `Api\ReportController@topCustomers` | `api.reports.top-customers` |
| GET | `/api/reports/export/sales` | `Api\ReportController@exportSales` | `api.reports.export.sales` |
| GET | `/api/reports/export/products` | `Api\ReportController@exportProducts` | `api.reports.export.products` |
| GET | `/api/reports/export/customers` | `Api\ReportController@exportCustomers` | `api.reports.export.customers` |

---

## Nota sobre nombres de rutas

Las rutas web y API tienen nombres separados para evitar conflictos:
- Web: `products.index`, `orders.index`, `customers.index`...
- API: `api.products.index`, `api.orders.index`, `api.customers.index`...

Esto es crítico porque `route('orders.index')` en Blade debe apuntar a `/orders` (web), no a `/api/orders`.
