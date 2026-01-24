<?php

declare(strict_types=1);

namespace JaapTech\NepaliPayment;

use Illuminate\Support\ServiceProvider;
use JaapTech\NepaliPayment\Services\PaymentManager;
use Kbk\NepaliPaymentGateway\Epay\ConnectIps;
use Kbk\NepaliPaymentGateway\Epay\Esewa;
use Kbk\NepaliPaymentGateway\Epay\Khalti;

class NepaliPaymentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/nepali-payment.php', 'nepali-payment');

        $this->app->singleton(PaymentManager::class, fn() => new PaymentManager());

        $this->app->singleton(Esewa::class, fn() => new Esewa(
            config('nepali-payment.esewa.product_code'),
            config('nepali-payment.esewa.secret_key'),
        ));

        $this->app->singleton(Khalti::class, fn() => new Khalti(
            config('nepali-payment.khalti.secret_key'),
            config('nepali-payment.khalti.environment'),
        ));

        $this->app->singleton(ConnectIps::class, fn() => new ConnectIps([
            'base_url' => config('nepali-payment.connectips.environment') === 'test' ? 'https://uat.connectips.com' : 'https://connectips.com',
            'merchant_id' => config('nepali-payment.connectips.merchant_id'),
            'app_id' => config('nepali-payment.connectips.app_id'),
            'app_name' => config('nepali-payment.connectips.app_name'),
            'password' => config('nepali-payment.connectips.password'),
            'private_key_path' => config('nepali-payment.connectips.private_key_path'),
        ]));
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/nepali-payment.php' => $this->app->configPath('nepali-payment.php')
        ], 'nepali-payment-config');
    }
}