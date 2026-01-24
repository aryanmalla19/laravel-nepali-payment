<?php

declare(strict_types=1);

namespace JaapTech\NepaliPayment;

use Illuminate\Support\ServiceProvider;
use JaapTech\NepaliPayment\Console\CheckConfig;
use JaapTech\NepaliPayment\Services\PaymentManager;

class NepaliPaymentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/nepali-payment.php', 'nepali-payment');

        $this->app->singleton(PaymentManager::class);
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/nepali-payment.php' => $this->app->configPath('nepali-payment.php')
        ], 'nepali-payment-config');

        if ($this->app->runningInConsole()) {
            $this->commands([
                CheckConfig::class,
            ]);
        }
    }
}