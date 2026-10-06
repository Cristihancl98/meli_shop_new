<?php

namespace App\Providers;

use App\Interfaces\AccountSettingRepositoryInterface;
use App\Interfaces\BulkPublishCodeRepositoryInterface;
use App\Interfaces\CustomerRepositoryInterface;
use App\Interfaces\ExternalCatalogRepositoryInterface;
use App\Interfaces\MeliNotificationRepositoryInterface;
use App\Interfaces\PostSaleMessageRepositoryInterface;
use App\Interfaces\QuestionRepositoryInterface;
use App\Interfaces\StoreRepositoryInterface;
use App\Interfaces\StoreSettingRepositoryInterface;
use App\Interfaces\UserRepositoryInterface;
use App\Interfaces\MercadolibreAccountRepositoryInterface;
use App\Interfaces\OrderRepositoryInterface;
use App\Interfaces\ProductRepositoryInterface;
use App\Repositories\AccountSettingRepository;
use App\Repositories\BulkPublishCodeRepository;
use App\Repositories\CustomerRepository;
use App\Repositories\MeliNotificationRepository;
use App\Repositories\MongoCatalogRepository;
use App\Repositories\PostSaleMessageRepository;
use App\Repositories\QuestionRepository;
use App\Repositories\StoreRepository;
use App\Repositories\StoreSettingRepository;
use App\Services\TenantManager;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use App\Repositories\UserRepository;
use App\Repositories\MercadolibreAccountRepository;
use App\Repositories\OrderRepository;
use App\Repositories\ProductRepository;
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

        $this->app->bind(AccountSettingRepositoryInterface::class, AccountSettingRepository::class);
        $this->app->bind(QuestionRepositoryInterface::class, QuestionRepository::class);
        $this->app->bind(PostSaleMessageRepositoryInterface::class, PostSaleMessageRepository::class);
        $this->app->bind(MeliNotificationRepositoryInterface::class, MeliNotificationRepository::class);
        $this->app->bind(BulkPublishCodeRepositoryInterface::class, BulkPublishCodeRepository::class);
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
        $this->app->bind(ExternalCatalogRepositoryInterface::class, MongoCatalogRepository::class);
        $this->app->bind(StoreRepositoryInterface::class, StoreRepository::class);
        $this->app->bind(StoreSettingRepositoryInterface::class, StoreSettingRepository::class);
        $this->app->singleton(TenantManager::class);
    }

    public function boot(): void
    {
        Schema::defaultStringLength(191);

        $this->makeQueueTenantAware();

        Gate::define('view-report',   [\App\Policies\ReportPolicy::class, 'view']);
        Gate::define('export-report', [\App\Policies\ReportPolicy::class, 'export']);
        Gate::define('view-settings',   [\App\Policies\SettingPolicy::class, 'view']);
        Gate::define('update-settings', [\App\Policies\SettingPolicy::class, 'update']);
    }

    private function makeQueueTenantAware(): void
    {
        Queue::createPayloadUsing(fn () => ['store_id' => $this->app->make(TenantManager::class)->current()?->id]);

        Event::listen(JobProcessing::class, function (JobProcessing $event) {
            $storeId = $event->job->payload()['store_id'] ?? null;

            if ($storeId) {
                $this->app->make(TenantManager::class)->activateById((int) $storeId);
            }
        });
    }
}
