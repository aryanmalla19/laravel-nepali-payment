<?php

declare(strict_types=1);

namespace JaapTech\NepaliPayment\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use JaapTech\NepaliPayment\NepaliPaymentServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'JaapTech\\NepaliPayment\\Tests\\Factories\\'.class_basename($modelName).'Factory'
        );
    }

    protected function getPackageProviders($app): array
    {
        return [
            NepaliPaymentServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        // Enable database integration for tests
        $app['config']->set('nepali-payment.database.enabled', true);

        // Set up minimal gateway configs for testing
        $app['config']->set('nepali-payment.esewa', [
            'product_code' => 'test_product',
            'secret_key' => 'test_secret',
            'success_url' => 'https://example.com/success',
            'failure_url' => 'https://example.com/failure',
        ]);

        $app['config']->set('nepali-payment.khalti', [
            'secret_key' => 'test_secret',
            'environment' => 'test',
            'success_url' => 'https://example.com/success',
            'website_url' => 'https://example.com',
        ]);

        $app['config']->set('nepali-payment.connectips', [
            'merchant_id' => 'test_merchant',
            'app_id' => 'test_app',
            'app_name' => 'Test App',
            'password' => 'test_password',
            'private_key_path' => __DIR__.'/TestCase.php',
            'environment' => 'test',
            'return_url' => 'https://example.com/return',
        ]);
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
