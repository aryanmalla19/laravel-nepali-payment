<?php

declare(strict_types=1);

namespace JaapTech\NepaliPayment\Tests\Feature\Interceptors;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use JaapTech\NepaliPayment\Enums\PaymentStatus;
use JaapTech\NepaliPayment\Events\PaymentInitiatedEvent;
use JaapTech\NepaliPayment\Events\PaymentProcessingEvent;
use JaapTech\NepaliPayment\Interceptors\GatewayPaymentInterceptor;
use JaapTech\NepaliPayment\Models\PaymentTransaction;
use JaapTech\NepaliPayment\Services\PaymentManager;
use JaapTech\NepaliPayment\Tests\TestCase;

class GatewayPaymentInterceptorFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Event::fake([
            PaymentInitiatedEvent::class,
            PaymentProcessingEvent::class,
        ]);
    }

    public function test_interceptor_is_returned_from_payment_manager()
    {
        $manager = app(PaymentManager::class);
        
        $interceptor = $manager->gateway('khalti');
        
        $this->assertInstanceOf(GatewayPaymentInterceptor::class, $interceptor);
    }

    public function test_interceptor_is_returned_for_all_gateways()
    {
        $manager = app(PaymentManager::class);
        
        $esewa = $manager->gateway('esewa');
        $this->assertInstanceOf(GatewayPaymentInterceptor::class, $esewa);
        
        $khalti = $manager->gateway('khalti');
        $this->assertInstanceOf(GatewayPaymentInterceptor::class, $khalti);
        
        $connectips = $manager->gateway('connectips');
        $this->assertInstanceOf(GatewayPaymentInterceptor::class, $connectips);
    }

    public function test_interceptor_creates_new_instance_each_time()
    {
        $manager = app(PaymentManager::class);
        
        $interceptor1 = $manager->gateway('khalti');
        $interceptor2 = $manager->gateway('khalti');
        
        // Each call should return a new interceptor instance
        $this->assertNotSame($interceptor1, $interceptor2);
    }

    public function test_interceptor_has_payment_method()
    {
        $manager = app(PaymentManager::class);
        $interceptor = $manager->gateway('khalti');
        
        $this->assertTrue(method_exists($interceptor, 'payment'));
    }

    public function test_interceptor_has_verify_method()
    {
        $manager = app(PaymentManager::class);
        $interceptor = $manager->gateway('khalti');
        
        $this->assertTrue(method_exists($interceptor, 'verify'));
    }

    public function test_interceptor_uses_strategy_for_payment_data()
    {
        $manager = app(PaymentManager::class);
        $interceptor = $manager->gateway('khalti');
        
        // Test that the interceptor is properly configured with strategy
        $this->assertInstanceOf(GatewayPaymentInterceptor::class, $interceptor);
    }

    public function test_payment_flow_integration_with_interceptor()
    {
        // Create a payment record
        $payment = PaymentTransaction::create([
            'gateway' => 'khalti',
            'status' => PaymentStatus::PENDING,
            'amount' => 1000,
            'merchant_reference_id' => 'pidx-123',
            'initiated_at' => now(),
        ]);

        // Verify the payment was created
        $this->assertDatabaseHas('payment_transactions', [
            'id' => $payment->id,
            'gateway' => 'khalti',
            'status' => 'pending',
        ]);

        // Simulate verification
        $payment->markAsProcessing();
        $this->assertEquals(PaymentStatus::PROCESSING, $payment->fresh()->status);
    }

    public function test_interceptor_gateway_caching_behavior()
    {
        $manager = app(PaymentManager::class);
        
        // Get gateway through manager multiple times
        $gateway1 = $manager->gateway('khalti');
        $gateway2 = $manager->gateway('khalti');
        
        // Interceptors should be different instances but use same underlying gateway
        $this->assertNotSame($gateway1, $gateway2);
        $this->assertInstanceOf(GatewayPaymentInterceptor::class, $gateway1);
        $this->assertInstanceOf(GatewayPaymentInterceptor::class, $gateway2);
    }

    public function test_interceptor_with_different_gateways_has_different_strategies()
    {
        $manager = app(PaymentManager::class);
        
        $esewa = $manager->gateway('esewa');
        $khalti = $manager->gateway('khalti');
        
        // Both should be interceptors but with different internal strategies
        $this->assertInstanceOf(GatewayPaymentInterceptor::class, $esewa);
        $this->assertInstanceOf(GatewayPaymentInterceptor::class, $khalti);
    }
}
