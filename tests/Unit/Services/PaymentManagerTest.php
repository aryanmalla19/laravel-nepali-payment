<?php

declare(strict_types=1);

namespace JaapTech\NepaliPayment\Tests\Unit\Services;

use Illuminate\Foundation\Testing\RefreshDatabase;
use JaapTech\NepaliPayment\Enums\NepaliPaymentGateway;
use JaapTech\NepaliPayment\Enums\PaymentStatus;
use JaapTech\NepaliPayment\Interceptors\GatewayPaymentInterceptor;
use JaapTech\NepaliPayment\Models\PaymentTransaction;
use JaapTech\NepaliPayment\Services\PaymentManager;
use JaapTech\NepaliPayment\Tests\TestCase;
use RuntimeException;

class PaymentManagerTest extends TestCase
{
    use RefreshDatabase;

    private PaymentManager $manager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->manager = app(PaymentManager::class);
    }

    public function test_gateway_returns_intercepted_gateway()
    {
        $gateway = $this->manager->gateway('khalti');

        $this->assertInstanceOf(GatewayPaymentInterceptor::class, $gateway);
    }

    public function test_gateway_returns_intercepted_gateway_for_enum()
    {
        $gateway = $this->manager->gateway(NepaliPaymentGateway::ESEWA);

        $this->assertInstanceOf(GatewayPaymentInterceptor::class, $gateway);
    }

    public function test_esewa_returns_intercepted_gateway()
    {
        $gateway = $this->manager->esewa();

        $this->assertInstanceOf(GatewayPaymentInterceptor::class, $gateway);
    }

    public function test_khalti_returns_intercepted_gateway()
    {
        $gateway = $this->manager->khalti();

        $this->assertInstanceOf(GatewayPaymentInterceptor::class, $gateway);
    }

    public function test_connectips_returns_intercepted_gateway()
    {
        $gateway = $this->manager->connectips();

        $this->assertInstanceOf(GatewayPaymentInterceptor::class, $gateway);
    }

    public function test_complete_payment_marks_payment_completed()
    {
        $payment = PaymentTransaction::create([
            'gateway' => 'KHALTI',
            'status' => PaymentStatus::PROCESSING,
            'amount' => 1000,
            'merchant_reference_id' => 'ref-123',
            'initiated_at' => now(),
        ]);

        $this->manager->completePayment($payment);

        $this->assertEquals(PaymentStatus::COMPLETED, $payment->fresh()->status);
    }

    public function test_find_payment_by_reference_returns_payment()
    {
        $payment = PaymentTransaction::create([
            'gateway' => 'KHALTI',
            'status' => PaymentStatus::PENDING,
            'amount' => 1000,
            'merchant_reference_id' => 'unique-ref',
            'initiated_at' => now(),
        ]);

        $found = $this->manager->findPaymentByReference('unique-ref');

        $this->assertNotNull($found);
        $this->assertEquals($payment->id, $found->id);
    }

    public function test_find_payment_by_reference_returns_null_when_not_found()
    {
        $found = $this->manager->findPaymentByReference('non-existent');

        $this->assertNull($found);
    }

    public function test_get_payments_by_status_returns_builder()
    {
        PaymentTransaction::create([
            'gateway' => 'KHALTI',
            'status' => PaymentStatus::COMPLETED,
            'amount' => 1000,
            'merchant_reference_id' => 'ref-1',
            'initiated_at' => now(),
        ]);

        PaymentTransaction::create([
            'gateway' => 'KHALTI',
            'status' => PaymentStatus::PENDING,
            'amount' => 2000,
            'merchant_reference_id' => 'ref-2',
            'initiated_at' => now(),
        ]);

        $completed = $this->manager->getPaymentsByStatus(PaymentStatus::COMPLETED)->get();

        $this->assertCount(1, $completed);
        $this->assertEquals(PaymentStatus::COMPLETED, $completed->first()->status);
    }

    public function test_get_payments_by_status_with_string()
    {
        PaymentTransaction::create([
            'gateway' => 'KHALTI',
            'status' => PaymentStatus::FAILED,
            'amount' => 1000,
            'merchant_reference_id' => 'ref-1',
            'initiated_at' => now(),
        ]);

        $failed = $this->manager->getPaymentsByStatus('failed')->get();

        $this->assertCount(1, $failed);
    }

    public function test_get_payments_by_gateway_returns_builder()
    {
        PaymentTransaction::create([
            'gateway' => 'khalti',
            'status' => PaymentStatus::PENDING,
            'amount' => 1000,
            'merchant_reference_id' => 'ref-1',
            'initiated_at' => now(),
        ]);

        PaymentTransaction::create([
            'gateway' => 'esewa',
            'status' => PaymentStatus::PENDING,
            'amount' => 2000,
            'merchant_reference_id' => 'ref-2',
            'initiated_at' => now(),
        ]);

        $khaltiPayments = $this->manager->getPaymentsByGateway('khalti')->get();

        $this->assertCount(1, $khaltiPayments);
        $this->assertEquals('khalti', $khaltiPayments->first()->gateway);
    }

    public function test_get_payments_by_gateway_with_enum()
    {
        PaymentTransaction::create([
            'gateway' => 'esewa',
            'status' => PaymentStatus::PENDING,
            'amount' => 1000,
            'merchant_reference_id' => 'ref-1',
            'initiated_at' => now(),
        ]);

        $esewaPayments = $this->manager->getPaymentsByGateway(NepaliPaymentGateway::ESEWA)->get();

        $this->assertCount(1, $esewaPayments);
    }

    public function test_get_payments_by_gateway_throws_exception_for_invalid_gateway()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unsupported gateway');

        $this->manager->getPaymentsByGateway('INVALID_GATEWAY');
    }

    public function test_get_payments_for_payable_returns_builder()
    {
        PaymentTransaction::create([
            'gateway' => 'KHALTI',
            'status' => PaymentStatus::PENDING,
            'amount' => 1000,
            'merchant_reference_id' => 'ref-1',
            'payable_type' => 'orders',
            'payable_id' => 1,
            'initiated_at' => now(),
        ]);

        PaymentTransaction::create([
            'gateway' => 'KHALTI',
            'status' => PaymentStatus::PENDING,
            'amount' => 2000,
            'merchant_reference_id' => 'ref-2',
            'payable_type' => 'orders',
            'payable_id' => 2,
            'initiated_at' => now(),
        ]);

        $payments = $this->manager->getPaymentsForPayable('orders', 1)->get();

        $this->assertCount(1, $payments);
        $this->assertEquals(1, $payments->first()->payable_id);
    }

    public function test_gateway_creates_new_interceptor_each_time()
    {
        $gateway1 = $this->manager->gateway('khalti');
        $gateway2 = $this->manager->gateway('khalti');

        // Interceptors should be new instances each time (not cached)
        $this->assertNotSame($gateway1, $gateway2);
    }

    public function test_manager_is_registered_as_singleton()
    {
        $manager1 = app(PaymentManager::class);
        $manager2 = app(PaymentManager::class);

        $this->assertSame($manager1, $manager2);
    }
}
