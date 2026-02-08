<?php

declare(strict_types=1);

namespace JaapTech\NepaliPayment\Tests\Unit\Services;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use JaapTech\NepaliPayment\Enums\PaymentStatus;
use JaapTech\NepaliPayment\Exceptions\DatabaseException;
use JaapTech\NepaliPayment\Models\PaymentTransaction;
use JaapTech\NepaliPayment\Services\PaymentService;
use JaapTech\NepaliPayment\Tests\TestCase;

class PaymentServiceTest extends TestCase
{
    use RefreshDatabase;

    private PaymentService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(PaymentService::class);
    }

    public function test_create_payment_creates_record_in_database()
    {
        $response = $this->service->createPayment(
            gateway: 'khalti',
            amount: 1000,
            gatewayPayloadData: ['amount' => 1000, 'purchase_order_id' => 'order-123'],
            gatewayResponseData: ['pidx' => 'pidx-123', 'amount' => 1000],
        );

        $this->assertInstanceOf(PaymentTransaction::class, $response);
        $this->assertDatabaseHas('payment_transactions', [
            'id' => $response->id,
            'gateway' => 'khalti',
            'amount' => 1000,
            'status' => 'pending',
        ]);
    }

    public function test_create_payment_extracts_reference_id_from_pidx()
    {
        $response = $this->service->createPayment(
            gateway: 'khalti',
            amount: 1000,
            gatewayResponseData: ['pidx' => 'pidx-123-abc'],
        );

        $this->assertEquals('pidx-123-abc', $response->merchant_reference_id);
    }

    public function test_create_payment_extracts_reference_id_from_transaction_uuid()
    {
        $response = $this->service->createPayment(
            gateway: 'esewa',
            amount: 1000,
            gatewayResponseData: ['transaction_uuid' => 'txn-uuid-123'],
        );

        $this->assertEquals('txn-uuid-123', $response->merchant_reference_id);
    }

    public function test_create_payment_generates_uuid_when_no_reference_id_found()
    {
        $response = $this->service->createPayment(
            gateway: 'khalti',
            amount: 1000,
            gatewayResponseData: ['other_field' => 'value'],
        );

        $this->assertTrue(Str::isUuid($response->merchant_reference_id));
    }

    public function test_create_payment_stores_gateway_payload_and_response()
    {
        $payload = ['amount' => 1000, 'order_id' => '123'];
        $responseData = ['pidx' => 'pidx-123', 'status' => 'success'];

        $payment = $this->service->createPayment(
            gateway: 'khalti',
            amount: 1000,
            gatewayPayloadData: $payload,
            gatewayResponseData: $responseData,
        );

        $this->assertEquals($payload, $payment->gateway_payload);
        $this->assertEquals($responseData, $payment->gateway_response);
    }

    public function test_create_payment_associates_with_model()
    {
        $payment = $this->service->createPayment(
            gateway: 'khalti',
            amount: 1000,
            gatewayResponseData: ['pidx' => 'pidx-123'],
            model: null, // We'll test with a real model later
        );

        $this->assertNull($payment->payable_type);
        $this->assertNull($payment->payable_id);
    }

    public function test_create_payment_throws_exception_when_database_disabled()
    {
        config(['nepali-payment.database.enabled' => false]);

        $this->expectException(DatabaseException::class);
        $this->expectExceptionMessage('Database integration is not enabled. Set NEPALI_PAYMENT_DATABASE_ENABLED=true in .env');

        $this->service->createPayment(
            gateway: 'khalti',
            amount: 1000,
        );
    }

    public function test_record_payment_verification_updates_status_to_processing()
    {
        $payment = PaymentTransaction::create([
            'gateway' => 'khalti',
            'status' => PaymentStatus::PENDING,
            'amount' => 1000,
            'merchant_reference_id' => 'ref-123',
            'initiated_at' => now(),
        ]);

        $this->service->recordPaymentVerification(
            payment: $payment,
            verificationData: ['status' => 'success'],
            isSuccess: true,
        );

        $freshPayment = $payment->fresh();
        $this->assertEquals(PaymentStatus::PROCESSING, $freshPayment->status);
        $this->assertNotNull($freshPayment->verified_at);
    }

    public function test_record_payment_verification_updates_status_to_failed()
    {
        $payment = PaymentTransaction::create([
            'gateway' => 'khalti',
            'status' => PaymentStatus::PENDING,
            'amount' => 1000,
            'merchant_reference_id' => 'ref-123',
            'initiated_at' => now(),
        ]);

        $this->service->recordPaymentVerification(
            payment: $payment,
            verificationData: ['status' => 'failed'],
            isSuccess: false,
        );

        $freshPayment = $payment->fresh();
        $this->assertEquals(PaymentStatus::FAILED, $freshPayment->status);
        $this->assertNotNull($freshPayment->failed_at);
    }

    public function test_record_payment_verification_throws_exception_when_database_disabled()
    {
        config(['nepali-payment.database.enabled' => false]);

        $payment = PaymentTransaction::create([
            'gateway' => 'khalti',
            'status' => PaymentStatus::PENDING,
            'amount' => 1000,
            'merchant_reference_id' => 'ref-123',
            'initiated_at' => now(),
        ]);

        $this->expectException(DatabaseException::class);

        $this->service->recordPaymentVerification(
            payment: $payment,
            verificationData: ['status' => 'success'],
            isSuccess: true,
        );
    }

    public function test_complete_payment_updates_status_to_completed()
    {
        $payment = PaymentTransaction::create([
            'gateway' => 'khalti',
            'status' => PaymentStatus::PROCESSING,
            'amount' => 1000,
            'merchant_reference_id' => 'ref-123',
            'initiated_at' => now(),
        ]);

        $this->service->completePayment($payment);

        $freshPayment = $payment->fresh();
        $this->assertEquals(PaymentStatus::COMPLETED, $freshPayment->status);
        $this->assertNotNull($freshPayment->completed_at);
    }

    public function test_complete_payment_throws_exception_when_database_disabled()
    {
        config(['nepali-payment.database.enabled' => false]);

        $payment = PaymentTransaction::create([
            'gateway' => 'khalti',
            'status' => PaymentStatus::PROCESSING,
            'amount' => 1000,
            'merchant_reference_id' => 'ref-123',
            'initiated_at' => now(),
        ]);

        $this->expectException(DatabaseException::class);

        $this->service->completePayment($payment);
    }

    public function test_fail_payment_updates_status_to_failed()
    {
        $payment = PaymentTransaction::create([
            'gateway' => 'khalti',
            'status' => PaymentStatus::PENDING,
            'amount' => 1000,
            'merchant_reference_id' => 'ref-123',
            'initiated_at' => now(),
        ]);

        $this->service->failPayment($payment);

        $freshPayment = $payment->fresh();
        $this->assertEquals(PaymentStatus::FAILED, $freshPayment->status);
        $this->assertNotNull($freshPayment->failed_at);
    }

    public function test_fail_payment_throws_exception_when_database_disabled()
    {
        config(['nepali-payment.database.enabled' => false]);

        $payment = PaymentTransaction::create([
            'gateway' => 'khalti',
            'status' => PaymentStatus::PENDING,
            'amount' => 1000,
            'merchant_reference_id' => 'ref-123',
            'initiated_at' => now(),
        ]);

        $this->expectException(DatabaseException::class);

        $this->service->failPayment($payment);
    }

    public function test_find_by_reference_returns_payment()
    {
        $payment = PaymentTransaction::create([
            'gateway' => 'khalti',
            'status' => PaymentStatus::PENDING,
            'amount' => 1000,
            'merchant_reference_id' => 'unique-ref-123',
            'initiated_at' => now(),
        ]);

        $found = $this->service->findByReference('unique-ref-123');

        $this->assertNotNull($found);
        $this->assertEquals($payment->id, $found->id);
    }

    public function test_find_by_reference_returns_null_when_not_found()
    {
        $found = $this->service->findByReference('non-existent-ref');

        $this->assertNull($found);
    }
}
