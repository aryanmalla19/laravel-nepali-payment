<?php

declare(strict_types=1);

namespace JaapTech\NepaliPayment;

use Illuminate\Support\ServiceProvider;
use JaapTech\NepaliPayment\Console\CheckConfig;
use JaapTech\NepaliPayment\Services\GatewayFactory;
use JaapTech\NepaliPayment\Services\PaymentManager;
use JaapTech\NepaliPayment\Services\PaymentTransactionQueryService;
use JaapTech\NepaliPayment\Services\PaymentService;
use JaapTech\NepaliPayment\Services\RefundService;

class NepaliPaymentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/nepali-payment.php', 'nepali-payment');

        // Register GatewayFactory with config dependency
        $this->app->singleton(GatewayFactory::class, function ($app) {
            return new GatewayFactory(
                $app->make('config')
            );
        });

        // Register payment services with proper dependency injection
        // PaymentQueryService has no dependencies
        $this->app->singleton(PaymentTransactionQueryService::class);

        // PaymentService depends on config and PaymentQueryService
        $this->app->singleton(PaymentService::class, function ($app) {
            return new PaymentService(
                $app->make('config'),
                $app->make(PaymentTransactionQueryService::class)
            );
        });

        // RefundService depends on config and PaymentService
        $this->app->singleton(RefundService::class, function ($app) {
            return new RefundService(
                $app->make('config'),
                $app->make(PaymentService::class)
            );
        });

        // PaymentManager depends on config, all 3 services, and GatewayFactory
        $this->app->singleton(PaymentManager::class, function ($app) {
            return new PaymentManager(
                $app->make('config'),
                $app->make(PaymentService::class),
                $app->make(RefundService::class),
                $app->make(PaymentTransactionQueryService::class),
                $app->make(GatewayFactory::class)
            );
        });
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/nepali-payment.php' => $this->app->configPath('nepali-payment.php'),
        ], 'nepali-payment-config');

        // Publish migrations
        $this->publishes([
            __DIR__.'/../database/migrations' => $this->app->databasePath('migrations'),
        ], 'nepali-payment-migrations');

        // Load migrations from package
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        if ($this->app->runningInConsole()) {
            $this->commands([
                CheckConfig::class,
            ]);
        }
    }
}
