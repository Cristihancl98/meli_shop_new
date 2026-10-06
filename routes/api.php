<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BulkPublishController;
use App\Http\Controllers\Api\CatalogController;
use App\Http\Controllers\Api\MeliCategoryController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\PostSaleController;
use App\Http\Controllers\Api\PricingController;
use App\Http\Controllers\Api\ProductPublishingController;
use App\Http\Controllers\Api\QuestionController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\Api\UserController;
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
Route::prefix('auth')->middleware('tenant.code')->group(function () {
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
Route::middleware('auth.jwt')->group(function () {

    Route::prefix('auth')->group(function () {
        Route::post('logout',  [AuthController::class, 'logout']);
        Route::post('refresh', [AuthController::class, 'refresh']);
        Route::get('profile',  [AuthController::class, 'profile']);
        Route::put('profile',  [ProfileController::class, 'update']);
    });

    /*
    |----------------------------------------------------------------------
    | Productos — publicación (migrado de Gestion / Inicio)
    |----------------------------------------------------------------------
    */
    Route::get('products/summary', [ProductPublishingController::class, 'summary'])->name('api.products.summary');
    Route::get('products/sku/{sku}', [ProductPublishingController::class, 'findBySku'])->name('api.products.by-sku');
    Route::post('products/publish', [ProductPublishingController::class, 'publish'])->name('api.products.publish');
    Route::post('products/{id}/pause', [ProductPublishingController::class, 'pause'])->whereNumber('id')->name('api.products.pause');
    Route::post('products/{id}/activate', [ProductPublishingController::class, 'activate'])->whereNumber('id')->name('api.products.activate');
    Route::patch('products/{id}/listing', [ProductPublishingController::class, 'updateListing'])->whereNumber('id')->name('api.products.listing');
    Route::post('products/{id}/archive', [ProductPublishingController::class, 'archive'])->whereNumber('id')->name('api.products.archive');

    Route::prefix('catalog')->group(function () {
        Route::get('sku/{sku}', [CatalogController::class, 'lookupSku'])->name('api.catalog.sku');
        Route::get('category/{categoryId}', [CatalogController::class, 'byCategory'])->name('api.catalog.category');
        Route::get('search', [CatalogController::class, 'searchByTitle'])->name('api.catalog.search');
    });

    Route::get('bulk-publish/codes', [BulkPublishController::class, 'index'])->name('api.bulk-publish.index');
    Route::post('bulk-publish/codes', [BulkPublishController::class, 'store'])->name('api.bulk-publish.store');

    Route::prefix('meli/categories')->group(function () {
        Route::get('/', [MeliCategoryController::class, 'index'])->name('api.meli.categories');
        Route::get('predict', [MeliCategoryController::class, 'predict'])->name('api.meli.categories.predict');
        Route::get('{categoryId}', [MeliCategoryController::class, 'show'])->name('api.meli.categories.show');
    });

    /*
    |----------------------------------------------------------------------
    | Configuración y precios (migrado de Configuraciones / Precio)
    |----------------------------------------------------------------------
    */
    Route::get('settings', [SettingController::class, 'index'])->name('api.settings.index');
    Route::put('settings', [SettingController::class, 'update'])->name('api.settings.update');
    Route::get('settings/reputation', [SettingController::class, 'reputation'])->name('api.settings.reputation');
    Route::get('settings/description-template', [SettingController::class, 'descriptionTemplate'])->name('api.settings.description-template');
    Route::get('pricing/calculate', [PricingController::class, 'calculate'])->name('api.pricing.calculate');

    /*
    |----------------------------------------------------------------------
    | Preguntas y posventa (migrado de Preguntas)
    |----------------------------------------------------------------------
    */
    Route::get('questions', [QuestionController::class, 'index'])->name('api.questions.index');
    Route::patch('questions/{id}/seen', [QuestionController::class, 'markSeen'])->whereNumber('id')->name('api.questions.seen');
    Route::post('questions/{id}/answer', [QuestionController::class, 'answer'])->whereNumber('id')->name('api.questions.answer');
    Route::get('post-sale/conversations', [PostSaleController::class, 'index'])->name('api.post-sale.index');
    Route::post('post-sale/conversations/{orderId}/messages', [PostSaleController::class, 'reply'])->whereNumber('orderId')->name('api.post-sale.reply');

    /*
    |----------------------------------------------------------------------
    | Notificaciones y usuarios (migrado de Gestion)
    |----------------------------------------------------------------------
    */
    Route::get('notifications', [NotificationController::class, 'index'])->name('api.notifications.index');
    Route::patch('notifications/{id}/read', [NotificationController::class, 'markAsRead'])->whereNumber('id')->name('api.notifications.read');

    Route::get('users', [UserController::class, 'index'])->name('api.users.index');
    Route::post('users', [UserController::class, 'store'])->name('api.users.store');
    Route::put('users/{id}', [UserController::class, 'update'])->whereNumber('id')->name('api.users.update');

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
    Route::put('orders/{id}/final-price', [OrderController::class, 'updateFinalPrice'])->whereNumber('id')->name('api.orders.final-price');
    Route::get('orders/{id}/shipping-label', [OrderController::class, 'shippingLabel'])->whereNumber('id')->name('api.orders.shipping-label');

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
