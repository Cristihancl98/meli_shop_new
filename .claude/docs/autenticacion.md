# Autenticación

El proyecto usa **dos mecanismos separados** según el contexto:

---

## Web — Laravel Session

**Guard**: `web` (driver `session`)  
**Middleware**: `WebAuthMiddleware` → alias `web.auth`

### Flujo de login
```
GET /dashboard (sin sesión)
    └─► WebAuthMiddleware → Auth::check() === false → redirect('/login')

GET /login → muestra formulario (resources/views/auth/login.blade.php)

POST /login { email, password }
    └─► Web\AuthController::login()
         └─► Auth::attempt() → crea sesión
              └─► redirect()->intended('/dashboard')

POST /logout
    └─► Auth::logout() + session()->invalidate() + redirect('/login')
```

### Archivos clave
| Archivo | Descripción |
|---------|-------------|
| `app/Http/Middleware/WebAuthMiddleware.php` | Comprueba `Auth::check()`, redirige a `/login` |
| `app/Http/Controllers/Web/AuthController.php` | `showLogin`, `login`, `logout` con sesión |
| `resources/views/auth/login.blade.php` | Vista de login (Bootstrap, sin layout) |
| `config/auth.php` | Guard `web` → driver `session` (por defecto de Laravel) |

### Rutas públicas (sin autenticación)
```
GET  /login
POST /login
POST /logout
```

---

## API — JWT (tymon/jwt-auth)

**Guard**: `api` (driver `jwt`)  
**Middleware**: `JwtMiddleware` → alias `jwt.auth`  
**Header requerido**: `Authorization: Bearer <token>`

### Flujo de login
```
POST /api/auth/login { email, password }
    └─► Api\AuthController::login()
         └─► JWTAuth::attempt() → retorna token
              └─► { success, data: { token, user, expires_in } }

GET /api/products  (Authorization: Bearer eyJ...)
    └─► JwtMiddleware::handle()
         └─► JWTAuth::parseToken()->authenticate() → ok → continúa
             (falla) → response()->json(['success'=>false,...], 401)
```

### Endpoints de auth API
```
POST /api/auth/login            → { token, user, expires_in }
POST /api/auth/register         → { token, user }
POST /api/auth/logout           ← Bearer token requerido
POST /api/auth/refresh          ← Bearer token requerido
GET  /api/auth/profile          ← Bearer token requerido
PUT  /api/auth/profile          ← Bearer token requerido
```

### Archivos clave
| Archivo | Descripción |
|---------|-------------|
| `app/Http/Middleware/JwtMiddleware.php` | Valida JWT, detecta web vs API, retorna JSON 401 |
| `app/Http/Controllers/Api/AuthController.php` | Login/logout/refresh/profile JWT |
| `config/jwt.php` | TTL, algoritmo, secret (env: `JWT_SECRET`) |

---

## Diferencias clave

| | Web (sesión) | API (JWT) |
|--|--|--|
| Token almacenado | Cookie de sesión de PHP | `Authorization` header |
| Login | `Auth::attempt()` | `JWTAuth::attempt()` |
| Logout | `Auth::logout()` | `JWTAuth::invalidate()` |
| Error sin auth | `redirect('/login')` | JSON `401` |
| Middleware | `web.auth` | `jwt.auth` |
| Rutas | `routes/web.php` | `routes/api.php` |

---

## Registro de middlewares

`bootstrap/app.php`:
```php
$middleware->alias([
    'jwt.auth' => JwtMiddleware::class,
    'web.auth' => WebAuthMiddleware::class,
    'role'     => RoleMiddleware::class,
]);
```
