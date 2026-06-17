<?php

namespace App\Providers;

use App\Events\CustomerSynced;
use App\Events\MeliTokenRefreshed;
use App\Events\OrderSynced;
use App\Events\ProductSynced;
use App\Events\SyncFailed;
use App\Interfaces\CustomerRepositoryInterface;
use App\Interfaces\MercadolibreAccountRepositoryInterface;
use App\Interfaces\OrderRepositoryInterface;
use App\Interfaces\ProductRepositoryInterface;
use App\Listeners\LogCustomerActivity;
use App\Listeners\LogTokenRefresh;
use App\Listeners\NotifyAdminOfFailure;
use App\Listeners\UpdateProductStatistics;
use App\Listeners\UpdateSalesStatistics;
use App\Repositories\CustomerRepository;
use App\Repositories\MercadolibreAccountRepository;
use App\Repositories\OrderRepository;
use App\Repositories\ProductRepository;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            MercadolibreAccountRepositoryInterface::class,
            MercadolibreAccountRepository::class
        );

        $this->app->bind(
            ProductRepositoryInterface::class,
            ProductRepository::class
        );

        $this->app->bind(
            OrderRepositoryInterface::class,
            OrderRepository::class
        );

        $this->app->bind(
            CustomerRepositoryInterface::class,
            CustomerRepository::class
        );
    }

    public function boot(): void
    {
        Schema::defaultStringLength(191);

        Event::listen(ProductSynced::class, UpdateProductStatistics::class);
        Event::listen(OrderSynced::class, UpdateSalesStatistics::class);
        Event::listen(CustomerSynced::class, LogCustomerActivity::class);
        Event::listen(MeliTokenRefreshed::class, LogTokenRefresh::class);
        Event::listen(SyncFailed::class, NotifyAdminOfFailure::class);

        Gate::define('view-report',   [\App\Policies\ReportPolicy::class, 'view']);
        Gate::define('export-report', [\App\Policies\ReportPolicy::class, 'export']);
    }
}
