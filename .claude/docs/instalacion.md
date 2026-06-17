# Instalación y Setup

## Requisitos

- PHP 8.2+
- MySQL 8.x
- Composer
- Cuenta de Mercado Libre Colombia con app registrada en developers.mercadolibre.com.co

---

## 1. Clonar y dependencias

```bash
git clone <repo>
cd meli_shop_new
composer install
```

---

## 2. Variables de entorno

```bash
cp .env.example .env
```

Editar `.env`:

```env
# App
APP_NAME="Marketplace Manager"
APP_URL=http://localhost:8000

# Base de datos (MySQL obligatorio, NO SQLite en producción)
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=marketplace_manager
DB_USERNAME=root
DB_PASSWORD=

# JWT
JWT_SECRET=          # se genera con: php artisan jwt:secret

# Queue (SIEMPRE database, no Redis)
QUEUE_CONNECTION=database

# MercadoLibre Colombia
MELI_CLIENT_ID=
MELI_CLIENT_SECRET=
MELI_REDIRECT_URI=http://localhost:8000/meli/callback

# AWS S3 (para imágenes de productos)
AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=
FILESYSTEM_DISK=s3

# Mail (para notificaciones de errores de sync)
MAIL_MAILER=smtp
MAIL_HOST=
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_FROM_ADDRESS=noreply@tudominio.com
```

---

## 3. Generar claves y migrar

```bash
php artisan key:generate
php artisan jwt:secret
php artisan migrate --seed
php artisan storage:link
```

El seeder crea:
- Usuario admin: `admin@marketplace.com` / `password`
- Roles: `admin`, `operator`

---

## 4. Levantar el servidor

```bash
# Terminal 1: servidor web
php artisan serve

# Terminal 2: queue worker (necesario para Jobs de sincronización)
php artisan queue:listen
```

Acceder en: `http://127.0.0.1:8000`

---

## 5. Conectar primera cuenta MeLi

1. Iniciar sesión con `admin@marketplace.com`
2. Ir a **Mis Tiendas** → **Conectar con MeLi**
3. Autorizar en MercadoLibre Colombia
4. La tienda queda activa automáticamente

---

## Scheduler (producción)

Agregar al crontab del servidor:

```bash
* * * * * cd /ruta/proyecto && php artisan schedule:run >> /dev/null 2>&1
```

Comandos programados:
| Comando | Frecuencia |
|---------|-----------|
| `sync:products` | Cada 30 min |
| `sync:orders` | Cada 10 min |
| `sync:customers` | Cada hora |
| `calculate:statistics` | Cada hora |

---

## App MeLi — Configuración en developers

Al registrar la app en el portal de developers de MeLi, configurar:

- **Redirect URI**: `https://tudominio.com/meli/callback`
- **Scopes requeridos**: `read`, `write`, `offline_access`
- **País**: Colombia (MCO)
