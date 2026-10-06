<?php

use App\Http\Controllers\Web\AccountController;
use App\Http\Controllers\Web\AuthController;
use App\Http\Controllers\Web\ConnectController;
use App\Http\Controllers\Web\CustomerController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\MeliAuthController;
use App\Http\Controllers\Web\NotificationController;
use App\Http\Controllers\Web\PostSaleController;
use App\Http\Controllers\Web\ProductListingController;
use App\Http\Controllers\Web\PublisherController;
use App\Http\Controllers\Web\QuestionController;
use App\Http\Controllers\Web\SettingController;
use App\Http\Controllers\Web\UserController;
use App\Http\Controllers\Web\OrderController;
use App\Http\Controllers\Web\ProductController;
use App\Http\Controllers\Web\ReportController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect('/dashboard'));

// Código de conexión de la tienda (primera pantalla, antes del login)
Route::get('/connect', [ConnectController::class, 'show'])->name('connect.show');
Route::post('/connect', [ConnectController::class, 'store'])->name('connect.store');
Route::post('/connect/change', [ConnectController::class, 'change'])->name('connect.change');

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

    // Publicación (migrado de Gestion / Inicio)
    Route::get('publisher', [PublisherController::class, 'create'])->name('publisher.create');
    Route::post('publisher', [PublisherController::class, 'store'])->name('publisher.store');
    Route::get('publisher/price', [PublisherController::class, 'price'])->name('publisher.price');
    Route::get('publisher/catalog', [PublisherController::class, 'catalog'])->name('publisher.catalog');
    Route::get('publisher/codes', [PublisherController::class, 'codes'])->name('publisher.codes');
    Route::post('publisher/codes', [PublisherController::class, 'storeCodes'])->name('publisher.codes.store');

    Route::post('products/{id}/pause', [ProductListingController::class, 'pause'])->whereNumber('id')->name('products.pause');
    Route::post('products/{id}/activate', [ProductListingController::class, 'activate'])->whereNumber('id')->name('products.activate');
    Route::patch('products/{id}/listing', [ProductListingController::class, 'update'])->whereNumber('id')->name('products.listing');
    Route::post('products/{id}/archive', [ProductListingController::class, 'archive'])->whereNumber('id')->name('products.archive');

    // Configuración (migrado de Configuraciones)
    Route::get('settings', [SettingController::class, 'edit'])->name('settings.edit');
    Route::put('settings', [SettingController::class, 'update'])->name('settings.update');

    // Preguntas y posventa (migrado de Preguntas)
    Route::get('questions', [QuestionController::class, 'index'])->name('questions.index');
    Route::patch('questions/{id}/seen', [QuestionController::class, 'seen'])->whereNumber('id')->name('questions.seen');
    Route::post('questions/{id}/answer', [QuestionController::class, 'answer'])->whereNumber('id')->name('questions.answer');
    Route::get('post-sale', [PostSaleController::class, 'index'])->name('post-sale.index');
    Route::post('post-sale/{orderId}/messages', [PostSaleController::class, 'reply'])->whereNumber('orderId')->name('post-sale.reply');

    // Notificaciones y usuarios
    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::patch('notifications/{id}/read', [NotificationController::class, 'read'])->whereNumber('id')->name('notifications.read');
    Route::get('users', [UserController::class, 'index'])->name('users.index');
    Route::post('users', [UserController::class, 'store'])->name('users.store');
    Route::get('users/{id}/edit', [UserController::class, 'edit'])->whereNumber('id')->name('users.edit');
    Route::put('users/{id}', [UserController::class, 'update'])->whereNumber('id')->name('users.update');

    // Productos
    Route::resource('products', ProductController::class);
    Route::post('products/{id}/sync', [ProductController::class, 'sync'])->name('products.sync');

    // Ventas
    Route::resource('orders', OrderController::class)->only(['index', 'show']);
    Route::post('orders/sync', [OrderController::class, 'sync'])->name('orders.sync');
    Route::put('orders/{id}/final-price', [OrderController::class, 'updateFinalPrice'])->whereNumber('id')->name('orders.final-price');
    Route::get('orders/{id}/shipping-label', [OrderController::class, 'shippingLabel'])->whereNumber('id')->name('orders.shipping-label');

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
