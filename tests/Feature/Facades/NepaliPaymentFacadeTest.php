<?php

declare(strict_types=1);

namespace JaapTech\NepaliPayment\Tests\Feature\Facades;

use Illuminate\Foundation\Testing\RefreshDatabase;
use JaapTech\NepaliPayment\Enums\PaymentStatus;
use JaapTech\NepaliPayment\Facades\NepaliPayment;
use JaapTech\NepaliPayment\Interceptors\GatewayPaymentInterceptor;
use JaapTech\NepaliPayment\Models\PaymentTransaction;
use JaapTech\NepaliPayment\Services\PaymentManager;
use JaapTech\NepaliPayment\Tests\TestCase;

class NepaliPaymentFacadeTest extends TestCase
{
    use RefreshDatabase;

    public function test_facade_resolves_payment_manager()
    {
        $this->assertInstanceOf(
            PaymentManager::class,
            NepaliPayment::getFacadeRoot()
        );
    }

    public function test_facade_provides_access_to_esewa_gateway()
    {
        $gateway = NepaliPayment::esewa();

        $this->assertNotNull($gateway);
        $this->assertInstanceOf(GatewayPaymentInterceptor::class, $gateway);
    }

    public function test_facade_provides_access_to_khalti_gateway()
    {
        $gateway = NepaliPayment::khalti();

        $this->assertNotNull($gateway);
        $this->assertInstanceOf(GatewayPaymentInterceptor::class, $gateway);
    }

    public function test_facade_provides_access_to_connectips_gateway()
    {
        $gateway = NepaliPayment::connectips();

        $this->assertNotNull($gateway);
        $this->assertInstanceOf(GatewayPaymentInterceptor::class, $gateway);
    }

    public function test_facade_can_find_payment_by_reference()
    {
        $payment = PaymentTransaction::create([
            'gateway' => 'khalti',
            'status' => PaymentStatus::PENDING,
            'amount' => 1000,
            'merchant_reference_id' => 'unique-ref-123',
            'initiated_at' => now(),
        ]);

        $found = NepaliPayment::findPaymentByReference('unique-ref-123');

        $this->assertNotNull($found);
        $this->assertEquals($payment->id, $found->id);
    }

    public function test_facade_can_complete_payment()
    {
        $payment = PaymentTransaction::create([
            'gateway' => 'khalti',
            'status' => PaymentStatus::PROCESSING,
            'amount' => 1000,
            'merchant_reference_id' => 'ref-123',
            'initiated_at' => now(),
        ]);

        NepaliPayment::completePayment($payment);

        $this->assertEquals(PaymentStatus::COMPLETED, $payment->fresh()->status);
    }

    public function test_facade_can_get_payments_by_status()
    {
        PaymentTransaction::create([
            'gateway' => 'khalti',
            'status' => PaymentStatus::COMPLETED,
            'amount' => 1000,
            'merchant_reference_id' => 'ref-1',
            'initiated_at' => now(),
        ]);

        $payments = NepaliPayment::getPaymentsByStatus('completed')->get();

        $this->assertCount(1, $payments);
    }

    public function test_facade_can_get_payments_by_gateway()
    {
        PaymentTransaction::create([
            'gateway' => 'khalti',
            'status' => PaymentStatus::PENDING,
            'amount' => 1000,
            'merchant_reference_id' => 'ref-1',
            'initiated_at' => now(),
        ]);

        $payments = NepaliPayment::getPaymentsByGateway('khalti')->get();

        $this->assertCount(1, $payments);
    }

    public function test_facade_can_get_payments_for_payable()
    {
        PaymentTransaction::create([
            'gateway' => 'khalti',
            'status' => PaymentStatus::PENDING,
            'amount' => 1000,
            'merchant_reference_id' => 'ref-1',
            'payable_type' => 'orders',
            'payable_id' => 123,
            'initiated_at' => now(),
        ]);

        $payments = NepaliPayment::getPaymentsForPayable('orders', 123)->get();

        $this->assertCount(1, $payments);
    }

    public function test_facade_returns_null_for_nonexistent_payment()
    {
        $found = NepaliPayment::findPaymentByReference('non-existent');

        $this->assertNull($found);
    }

    public function test_facade_provides_gateway_method()
    {
        $gateway = NepaliPayment::gateway('khalti');

        $this->assertNotNull($gateway);
        $this->assertInstanceOf(GatewayPaymentInterceptor::class, $gateway);
    }
}
