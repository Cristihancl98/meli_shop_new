<?php

use App\Http\Controllers\Web\AccountController;
use App\Http\Controllers\Web\AuthController;
use App\Http\Controllers\Web\CustomerController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\MeliAuthController;
use App\Http\Controllers\Web\OrderController;
use App\Http\Controllers\Web\ProductController;
use App\Http\Controllers\Web\ReportController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect('/dashboard'));

// Auth web (sin middleware)
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware('web.auth')->group(function () {

    // MercadoLibre OAuth
    Route::get('/meli/connect',  [MeliAuthController::class, 'connect'])->name('meli.connect');
    Route::get('/meli/callback', [MeliAuthController::class, 'callback'])->name('meli.callback');

    // Gestión de cuentas MeLi (multi-tienda)
    Route::get('accounts',                    [AccountController::class, 'index'])->name('accounts.index');
    Route::post('accounts/switch',            [AccountController::class, 'switch'])->name('accounts.switch');
    Route::delete('accounts/{id}/disconnect', [AccountController::class, 'disconnect'])->name('accounts.disconnect');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Productos
    Route::resource('products', ProductController::class);
    Route::post('products/{id}/sync', [ProductController::class, 'sync'])->name('products.sync');

    // Ventas
    Route::resource('orders', OrderController::class)->only(['index', 'show']);
    Route::post('orders/sync', [OrderController::class, 'sync'])->name('orders.sync');

    // Clientes
    Route::resource('customers', CustomerController::class)->only(['index', 'show']);

    // Reportes
    Route::prefix('reports')->group(function () {
        Route::get('sales',            [ReportController::class, 'sales'])->name('reports.sales');
        Route::get('products',         [ReportController::class, 'products'])->name('reports.products');
        Route::get('customers',        [ReportController::class, 'customers'])->name('reports.customers');
        Route::get('export/sales',     [ReportController::class, 'exportSales'])->name('reports.export.sales');
        Route::get('export/products',  [ReportController::class, 'exportProducts'])->name('reports.export.products');
        Route::get('export/customers', [ReportController::class, 'exportCustomers'])->name('reports.export.customers');
    });
});
