# Base de Datos

**Motor**: MySQL 8.x  
**Charset**: `utf8mb4` / `utf8mb4_unicode_ci`  
**String length por defecto**: 191 (configurado en `AppServiceProvider::boot()`)

---

## Orden de migraciones (respetar FK)

```
1.  users
2.  roles
3.  user_roles                 (pivot)
4.  mercadolibre_accounts
5.  categories
6.  products
7.  customers
8.  orders
9.  order_items
10. product_statistics
11. sales_statistics
12. sync_logs
```

---

## Tablas

### `users`
| Campo | Tipo | Notas |
|-------|------|-------|
| id | bigint PK | |
| name | varchar(191) | |
| email | varchar(191) UNIQUE | |
| password | varchar | bcrypt |
| timestamps | | |

### `roles`
| Campo | Tipo | Notas |
|-------|------|-------|
| id | bigint PK | |
| name | varchar(50) | `admin`, `operator` |

### `user_roles` (pivot)
| Campo | Tipo |
|-------|------|
| user_id | FK → users |
| role_id | FK → roles |

### `mercadolibre_accounts`
| Campo | Tipo | Notas |
|-------|------|-------|
| id | bigint PK | |
| user_id | FK → users | |
| meli_user_id | varchar UNIQUE | ID vendedor en MeLi |
| access_token | text | hidden en serialización |
| refresh_token | text | hidden en serialización |
| expires_at | datetime | |
| nickname | varchar(191) | nombre tienda (de /users/me) |
| email | varchar(191) nullable | |
| is_active | boolean | false = desconectada |
| timestamps | | |

### `categories`
| Campo | Tipo | Notas |
|-------|------|-------|
| id | bigint PK | |
| meli_category_id | varchar | |
| name | varchar(191) | |
| parent_id | FK → categories nullable | subcategorías |
| timestamps | | |

### `products`
| Campo | Tipo | Notas |
|-------|------|-------|
| id | bigint PK | |
| mercadolibre_account_id | FK | scope por tienda |
| category_id | FK nullable | |
| meli_item_id | varchar(50) UNIQUE | |
| title | varchar(500) | |
| price | decimal(12,2) | COP |
| original_price | decimal(12,2) nullable | |
| stock | int | |
| sold_quantity | int default 0 | |
| status | enum | `active`, `paused`, `closed` |
| thumbnail | varchar(500) nullable | URL S3 o MeLi |
| permalink | varchar(500) nullable | |
| deleted_at | timestamp | soft delete |
| timestamps | | |

### `customers`
| Campo | Tipo | Notas |
|-------|------|-------|
| id | bigint PK | |
| mercadolibre_account_id | FK | |
| meli_customer_id | varchar(50) UNIQUE | |
| name | varchar(300) | |
| nickname | varchar(191) nullable | |
| email | varchar(191) nullable | |
| total_orders | int default 0 | |
| total_spent | decimal(12,2) | COP |
| deleted_at | timestamp | soft delete |
| timestamps | | |

### `orders`
| Campo | Tipo | Notas |
|-------|------|-------|
| id | bigint PK | |
| mercadolibre_account_id | FK | |
| customer_id | FK nullable | |
| meli_order_id | varchar(50) UNIQUE | |
| status | varchar(50) | `paid`, `pending`, `cancelled`... |
| payment_status | varchar(50) nullable | |
| total_amount | decimal(12,2) | COP |
| currency_id | varchar(10) default `COP` | |
| order_date | timestamp | |
| deleted_at | timestamp | soft delete |
| timestamps | | |

### `order_items`
| Campo | Tipo |
|-------|------|
| id | bigint PK |
| order_id | FK → orders |
| product_id | FK → products nullable |
| meli_item_id | varchar(50) |
| title | varchar(500) |
| quantity | int |
| unit_price | decimal(12,2) |
| total_price | decimal(12,2) |
| timestamps | |

### `product_statistics`
| Campo | Tipo |
|-------|------|
| id | bigint PK |
| product_id | FK → products |
| mercadolibre_account_id | FK |
| date | date |
| views | int |
| sold_quantity | int |
| revenue | decimal(12,2) |
| timestamps | |

### `sales_statistics`
| Campo | Tipo |
|-------|------|
| id | bigint PK |
| mercadolibre_account_id | FK |
| date | date |
| total_orders | int |
| total_revenue | decimal(12,2) |
| average_ticket | decimal(12,2) |
| timestamps | |

### `sync_logs`
| Campo | Tipo | Notas |
|-------|------|-------|
| id | bigint PK | |
| mercadolibre_account_id | FK | |
| type | enum | `products`, `orders`, `customers`, `statistics`, `webhook` |
| status | enum | `pending`, `running`, `success`, `error` |
| records_processed | int | |
| error_message | text nullable | |
| started_at | timestamp nullable | |
| finished_at | timestamp nullable | |
| timestamps | | |

---

## Relaciones clave

```
User ──1:N──► MercadolibreAccount
User ──N:N──► Role  (via user_roles)

MercadolibreAccount ──1:N──► Product
MercadolibreAccount ──1:N──► Order
MercadolibreAccount ──1:N──► Customer
MercadolibreAccount ──1:N──► SyncLog
MercadolibreAccount ──1:N──► SalesStatistic

Order ──N:1──► Customer
Order ──1:N──► OrderItem
OrderItem ──N:1──► Product

Product ──1:N──► ProductStatistic
Category ──1:N──► Product
Category ──1:N──► Category  (subcategorías)
```
