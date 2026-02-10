<?php

declare(strict_types=1);

namespace JaapTech\NepaliPayment\Tests\Feature\Integration;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use JaapTech\NepaliPayment\Enums\PaymentStatus;
use JaapTech\NepaliPayment\Events\PaymentCompletedEvent;
use JaapTech\NepaliPayment\Events\PaymentFailedEvent;
use JaapTech\NepaliPayment\Events\PaymentInitiatedEvent;
use JaapTech\NepaliPayment\Events\PaymentProcessingEvent;
use JaapTech\NepaliPayment\Facades\NepaliPayment;
use JaapTech\NepaliPayment\Models\PaymentTransaction;
use JaapTech\NepaliPayment\Tests\TestCase;

class PaymentFlowIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Event::fake([
            PaymentInitiatedEvent::class,
            PaymentProcessingEvent::class,
            PaymentCompletedEvent::class,
            PaymentFailedEvent::class,
        ]);
    }

    public function test_complete_payment_flow_from_initiation_to_completion()
    {
        // Step 1: Create a payment record
        $payment = PaymentTransaction::create([
            'gateway' => 'khalti',
            'status' => PaymentStatus::PENDING,
            'amount' => 1000,
            'merchant_reference_id' => 'pidx-123',
            'gateway_payload' => ['amount' => 1000],
            'gateway_response' => ['pidx' => 'pidx-123'],
            'initiated_at' => now(),
        ]);

        $this->assertDatabaseHas('payment_transactions', [
            'id' => $payment->id,
            'status' => 'pending',
        ]);

        // Step 2: Mark as processing (simulating verification)
        $payment->markAsProcessing();

        $this->assertEquals(PaymentStatus::PROCESSING, $payment->fresh()->status);
        $this->assertNotNull($payment->fresh()->verified_at);

        // Step 3: Complete the payment
        NepaliPayment::completePayment($payment);

        $this->assertEquals(PaymentStatus::COMPLETED, $payment->fresh()->status);
        $this->assertNotNull($payment->fresh()->completed_at);
    }

    public function test_payment_flow_with_failure()
    {
        // Step 1: Create a payment record
        $payment = PaymentTransaction::create([
            'gateway' => 'khalti',
            'status' => PaymentStatus::PENDING,
            'amount' => 1000,
            'merchant_reference_id' => 'pidx-123',
            'initiated_at' => now(),
        ]);

        // Step 2: Mark as failed
        $payment->markAsFailed();

        $this->assertEquals(PaymentStatus::FAILED, $payment->fresh()->status);
        $this->assertNotNull($payment->fresh()->failed_at);
    }

    public function test_payment_lifecycle_with_model_relationships()
    {
        // Create payment with polymorphic relationship
        $payment = PaymentTransaction::create([
            'gateway' => 'khalti',
            'status' => PaymentStatus::COMPLETED,
            'amount' => 1000,
            'merchant_reference_id' => 'ref-123',
            'payable_type' => 'orders',
            'payable_id' => 456,
            'initiated_at' => now(),
        ]);

        // Test relationship data is stored
        $this->assertEquals('orders', $payment->payable_type);
        $this->assertEquals(456, $payment->payable_id);

        // Test querying by payable
        $payments = NepaliPayment::getPaymentsForPayable('orders', 456)->get();
        $this->assertCount(1, $payments);
        $this->assertEquals($payment->id, $payments->first()->id);
    }

    public function test_multiple_payments_flow()
    {
        // Create multiple payments
        $payment1 = PaymentTransaction::create([
            'gateway' => 'khalti',
            'status' => PaymentStatus::COMPLETED,
            'amount' => 1000,
            'merchant_reference_id' => 'ref-1',
            'initiated_at' => now(),
        ]);

        $payment2 = PaymentTransaction::create([
            'gateway' => 'esewa',
            'status' => PaymentStatus::PENDING,
            'amount' => 2000,
            'merchant_reference_id' => 'ref-2',
            'initiated_at' => now(),
        ]);

        $payment3 = PaymentTransaction::create([
            'gateway' => 'khalti',
            'status' => PaymentStatus::FAILED,
            'amount' => 500,
            'merchant_reference_id' => 'ref-3',
            'initiated_at' => now(),
        ]);

        // Test querying
        $this->assertEquals(3, PaymentTransaction::count());
        $this->assertEquals(1, PaymentTransaction::completed()->count());
        $this->assertEquals(1, PaymentTransaction::pending()->count());
        $this->assertEquals(1, PaymentTransaction::failed()->count());

        // Test gateway filtering
        $khaltiPayments = NepaliPayment::getPaymentsByGateway('khalti')->get();
        $this->assertCount(2, $khaltiPayments);
    }

    public function test_payment_status_transitions()
    {
        $payment = PaymentTransaction::create([
            'gateway' => 'khalti',
            'status' => PaymentStatus::PENDING,
            'amount' => 1000,
            'merchant_reference_id' => 'ref-123',
            'initiated_at' => now(),
        ]);

        // PENDING -> PROCESSING
        $payment->markAsProcessing();
        $this->assertEquals(PaymentStatus::PROCESSING, $payment->fresh()->status);

        // PROCESSING -> COMPLETED
        $payment->markAsCompleted();
        $this->assertEquals(PaymentStatus::COMPLETED, $payment->fresh()->status);

        // Create another payment for failed transition
        $payment2 = PaymentTransaction::create([
            'gateway' => 'khalti',
            'status' => PaymentStatus::PENDING,
            'amount' => 500,
            'merchant_reference_id' => 'ref-456',
            'initiated_at' => now(),
        ]);

        // PENDING -> FAILED
        $payment2->markAsFailed();
        $this->assertEquals(PaymentStatus::FAILED, $payment2->fresh()->status);
    }

    public function test_payment_querying_and_filtering_integration()
    {
        // Create payments with different attributes
        PaymentTransaction::create([
            'gateway' => 'khalti',
            'status' => PaymentStatus::COMPLETED,
            'amount' => 1000,
            'merchant_reference_id' => 'khalti-complete',
            'payable_type' => 'orders',
            'payable_id' => 1,
            'initiated_at' => now()->subDays(2),
        ]);

        PaymentTransaction::create([
            'gateway' => 'khalti',
            'status' => PaymentStatus::PENDING,
            'amount' => 2000,
            'merchant_reference_id' => 'khalti-pending',
            'payable_type' => 'orders',
            'payable_id' => 2,
            'initiated_at' => now()->subDay(),
        ]);

        PaymentTransaction::create([
            'gateway' => 'esewa',
            'status' => PaymentStatus::COMPLETED,
            'amount' => 1500,
            'merchant_reference_id' => 'esewa-complete',
            'payable_type' => 'invoices',
            'payable_id' => 1,
            'initiated_at' => now(),
        ]);

        // Test complex querying
        $completedKhalti = PaymentTransaction::byGateway('khalti')
            ->byStatus('completed')
            ->get();
        $this->assertCount(1, $completedKhalti);

        $ordersPayments = NepaliPayment::getPaymentsForPayable('orders', 1)->get();
        $this->assertCount(1, $ordersPayments);

        $invoicesPayments = NepaliPayment::getPaymentsForPayable('invoices', 1)->get();
        $this->assertCount(1, $invoicesPayments);
    }

    public function test_facade_integration_with_all_gateways()
    {
        // Test that all gateways are accessible through facade
        $esewa = NepaliPayment::esewa();
        $this->assertNotNull($esewa);

        $khalti = NepaliPayment::khalti();
        $this->assertNotNull($khalti);

        $connectips = NepaliPayment::connectips();
        $this->assertNotNull($connectips);

        // Test gateway method
        $gateway = NepaliPayment::gateway('khalti');
        $this->assertNotNull($gateway);
    }
}
