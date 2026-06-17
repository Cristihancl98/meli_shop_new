<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\ReportController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Auth Routes (públicas)
|--------------------------------------------------------------------------
*/
Route::prefix('auth')->group(function () {
    Route::post('login',          [AuthController::class, 'login']);
    Route::post('register',       [AuthController::class, 'register']);
    Route::post('forgot-password',[AuthController::class, 'forgotPassword']);
    Route::post('reset-password', [AuthController::class, 'resetPassword']);
});

/*
|--------------------------------------------------------------------------
| Rutas protegidas con JWT
|--------------------------------------------------------------------------
*/
Route::middleware('jwt.auth')->group(function () {

    Route::prefix('auth')->group(function () {
        Route::post('logout',  [AuthController::class, 'logout']);
        Route::post('refresh', [AuthController::class, 'refresh']);
        Route::get('profile',  [AuthController::class, 'profile']);
        Route::put('profile',  [ProfileController::class, 'update']);
    });

    /*
    |----------------------------------------------------------------------
    | Productos
    |----------------------------------------------------------------------
    */
    Route::apiResource('products', ProductController::class)->names([
        'index'   => 'api.products.index',
        'store'   => 'api.products.store',
        'show'    => 'api.products.show',
        'update'  => 'api.products.update',
        'destroy' => 'api.products.destroy',
    ]);
    Route::post('products/{id}/sync', [ProductController::class, 'sync'])->name('api.products.sync');

    /*
    |----------------------------------------------------------------------
    | Dashboard (Fase 9)
    |----------------------------------------------------------------------
    */
    Route::prefix('dashboard')->group(function () {
        Route::get('/',             [DashboardController::class, 'index'])->name('api.dashboard');
        Route::get('/top-products', [DashboardController::class, 'topProducts'])->name('api.dashboard.top-products');
        Route::get('/sales-summary',[DashboardController::class, 'salesSummary'])->name('api.dashboard.sales-summary');
        Route::get('/alerts',       [DashboardController::class, 'alerts'])->name('api.dashboard.alerts');
    });

    /*
    |----------------------------------------------------------------------
    | Ventas (Fase 7)
    |----------------------------------------------------------------------
    */
    Route::apiResource('orders', OrderController::class)->only(['index', 'show'])->names([
        'index' => 'api.orders.index',
        'show'  => 'api.orders.show',
    ]);
    Route::post('orders/sync', [OrderController::class, 'sync'])->name('api.orders.sync');

    /*
    |----------------------------------------------------------------------
    | Clientes (Fase 8)
    |----------------------------------------------------------------------
    */
    Route::apiResource('customers', CustomerController::class)->only(['index', 'show'])->names([
        'index' => 'api.customers.index',
        'show'  => 'api.customers.show',
    ]);
    Route::get('customers/{id}/orders', [CustomerController::class, 'orders'])->name('api.customers.orders');

    /*
    |----------------------------------------------------------------------
    | Reportes (Fase 10)
    |----------------------------------------------------------------------
    */
    Route::prefix('reports')->group(function () {
        Route::get('sales',            [ReportController::class, 'sales'])->name('api.reports.sales');
        Route::get('top-products',     [ReportController::class, 'topProducts'])->name('api.reports.top-products');
        Route::get('top-customers',    [ReportController::class, 'topCustomers'])->name('api.reports.top-customers');
        Route::get('export/sales',     [ReportController::class, 'exportSales'])->name('api.reports.export.sales');
        Route::get('export/products',  [ReportController::class, 'exportProducts'])->name('api.reports.export.products');
        Route::get('export/customers', [ReportController::class, 'exportCustomers'])->name('api.reports.export.customers');
    });
});

/*
|--------------------------------------------------------------------------
| Webhook Mercado Libre (público — sin JWT, usa firma propia de MeLi)
|--------------------------------------------------------------------------
*/
Route::post('webhooks/mercadolibre', [\App\Http\Controllers\Api\WebhookController::class, 'handle'])
    ->name('webhooks.mercadolibre');
