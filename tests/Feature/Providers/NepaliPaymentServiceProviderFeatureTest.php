<?php

declare(strict_types=1);

namespace JaapTech\NepaliPayment\Tests\Feature\Providers;

use JaapTech\NepaliPayment\Console\CheckConfig;
use JaapTech\NepaliPayment\Factories\GatewayFactory;
use JaapTech\NepaliPayment\NepaliPaymentServiceProvider;
use JaapTech\NepaliPayment\Services\PaymentManager;
use JaapTech\NepaliPayment\Services\PaymentService;
use JaapTech\NepaliPayment\Services\PaymentTransactionQueryService;
use JaapTech\NepaliPayment\Tests\TestCase;

class NepaliPaymentServiceProviderFeatureTest extends TestCase
{
    public function test_service_provider_registers_all_services()
    {
        // Test that all services can be resolved from the container
        $this->assertInstanceOf(GatewayFactory::class, app(GatewayFactory::class));
        $this->assertInstanceOf(PaymentService::class, app(PaymentService::class));
        $this->assertInstanceOf(PaymentTransactionQueryService::class, app(PaymentTransactionQueryService::class));
        $this->assertInstanceOf(PaymentManager::class, app(PaymentManager::class));
    }

    public function test_service_provider_registers_commands()
    {
        $this->artisan('list')
            ->assertSuccessful();

        // Verify command is available
        $command = app(CheckConfig::class);
        $this->assertInstanceOf(CheckConfig::class, $command);
    }

    public function test_service_provider_merges_config()
    {
        $config = config('nepali-payment');

        $this->assertIsArray($config);
        $this->assertArrayHasKey('database', $config);
        $this->assertArrayHasKey('esewa', $config);
        $this->assertArrayHasKey('khalti', $config);
        $this->assertArrayHasKey('connectips', $config);
    }

    public function test_service_provider_registers_singletons()
    {
        // Test that services are registered as singletons
        $factory1 = app(GatewayFactory::class);
        $factory2 = app(GatewayFactory::class);
        $this->assertSame($factory1, $factory2);

        $manager1 = app(PaymentManager::class);
        $manager2 = app(PaymentManager::class);
        $this->assertSame($manager1, $manager2);
    }

    public function test_config_values_are_accessible()
    {
        // Test default config values
        $this->assertIsBool(config('nepali-payment.database.enabled'));
        $this->assertIsArray(config('nepali-payment.esewa'));
        $this->assertIsArray(config('nepali-payment.khalti'));
        $this->assertIsArray(config('nepali-payment.connectips'));
    }

    public function test_migrations_are_loadable()
    {
        // Verify migrations can be loaded
        $this->artisan('migrate:status')
            ->assertSuccessful();
    }

    public function test_service_provider_is_loaded()
    {
        $provider = app()->getProvider(NepaliPaymentServiceProvider::class);
        $this->assertInstanceOf(NepaliPaymentServiceProvider::class, $provider);
    }

    public function test_all_gateways_configured_in_test_environment()
    {
        // Verify test environment has gateway configs
        $this->assertNotNull(config('nepali-payment.esewa.product_code'));
        $this->assertNotNull(config('nepali-payment.esewa.secret_key'));
        $this->assertNotNull(config('nepali-payment.khalti.secret_key'));
        $this->assertNotNull(config('nepali-payment.connectips.merchant_id'));
    }
}
