<?php

declare(strict_types=1);

namespace JaapTech\NepaliPayment\Tests\Unit\Models;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use JaapTech\NepaliPayment\Enums\PaymentStatus;
use JaapTech\NepaliPayment\Models\PaymentRefund;
use JaapTech\NepaliPayment\Models\PaymentTransaction;
use JaapTech\NepaliPayment\Tests\TestCase;

class PaymentTransactionTest extends TestCase
{
    use RefreshDatabase;

    // ========== Basic Model Tests ==========

    public function test_payment_transaction_can_be_created()
    {
        $transaction = PaymentTransaction::create([
            'gateway' => 'khalti',
            'status' => PaymentStatus::PENDING,
            'amount' => 1000.00,
            'currency' => 'NPR',
            'merchant_reference_id' => Str::uuid()->toString(),
            'initiated_at' => now(),
        ]);

        $this->assertDatabaseHas('payment_transactions', [
            'id' => $transaction->id,
            'gateway' => 'khalti',
            'amount' => 1000.00,
        ]);
    }

    public function test_payment_transaction_uses_uuid_as_primary_key()
    {
        $transaction = PaymentTransaction::create([
            'gateway' => 'khalti',
            'status' => PaymentStatus::PENDING,
            'amount' => 100.00,
            'merchant_reference_id' => Str::uuid()->toString(),
            'initiated_at' => now(),
        ]);

        $this->assertTrue(Str::isUuid($transaction->id));
    }

    public function test_payment_transaction_has_correct_casts()
    {
        $transaction = PaymentTransaction::create([
            'gateway' => 'khalti',
            'status' => PaymentStatus::PENDING,
            'amount' => 100.50,
            'currency' => 'NPR',
            'merchant_reference_id' => 'test-123',
            'gateway_response' => ['key' => 'value'],
            'gateway_payload' => ['amount' => 100],
            'initiated_at' => now(),
        ]);

        $this->assertInstanceOf(PaymentStatus::class, $transaction->status);
        $this->assertIsFloat($transaction->amount);
        $this->assertIsArray($transaction->gateway_response);
        $this->assertIsArray($transaction->gateway_payload);
        $this->assertInstanceOf(\DateTime::class, $transaction->initiated_at);
    }

    // ========== Scope Tests ==========

    public function test_scope_by_gateway_filters_correctly()
    {
        PaymentTransaction::create([
            'gateway' => 'khalti',
            'status' => PaymentStatus::PENDING,
            'amount' => 100,
            'merchant_reference_id' => 'khalti-1',
            'initiated_at' => now(),
        ]);

        PaymentTransaction::create([
            'gateway' => 'esewa',
            'status' => PaymentStatus::PENDING,
            'amount' => 200,
            'merchant_reference_id' => 'esewa-1',
            'initiated_at' => now(),
        ]);

        $khaltiPayments = PaymentTransaction::byGateway('khalti')->get();

        $this->assertCount(1, $khaltiPayments);
        $this->assertEquals('khalti', $khaltiPayments->first()->gateway);
    }

    public function test_scope_by_status_with_enum()
    {
        PaymentTransaction::create([
            'gateway' => 'khalti',
            'status' => PaymentStatus::COMPLETED,
            'amount' => 100,
            'merchant_reference_id' => 'completed-1',
            'initiated_at' => now(),
        ]);

        PaymentTransaction::create([
            'gateway' => 'khalti',
            'status' => PaymentStatus::PENDING,
            'amount' => 200,
            'merchant_reference_id' => 'pending-1',
            'initiated_at' => now(),
        ]);

        $completed = PaymentTransaction::byStatus(PaymentStatus::COMPLETED)->get();

        $this->assertCount(1, $completed);
        $this->assertEquals(PaymentStatus::COMPLETED, $completed->first()->status);
    }

    public function test_scope_by_status_with_string()
    {
        PaymentTransaction::create([
            'gateway' => 'khalti',
            'status' => PaymentStatus::FAILED,
            'amount' => 100,
            'merchant_reference_id' => 'failed-1',
            'initiated_at' => now(),
        ]);

        $failed = PaymentTransaction::byStatus('failed')->get();

        $this->assertCount(1, $failed);
    }

    public function test_scope_completed()
    {
        PaymentTransaction::create([
            'gateway' => 'khalti',
            'status' => PaymentStatus::COMPLETED,
            'amount' => 100,
            'merchant_reference_id' => 'completed-1',
            'initiated_at' => now(),
        ]);

        PaymentTransaction::create([
            'gateway' => 'khalti',
            'status' => PaymentStatus::PENDING,
            'amount' => 200,
            'merchant_reference_id' => 'pending-1',
            'initiated_at' => now(),
        ]);

        $completed = PaymentTransaction::completed()->get();

        $this->assertCount(1, $completed);
        $this->assertTrue($completed->first()->isCompleted());
    }

    public function test_scope_failed()
    {
        PaymentTransaction::create([
            'gateway' => 'khalti',
            'status' => PaymentStatus::FAILED,
            'amount' => 100,
            'merchant_reference_id' => 'failed-1',
            'initiated_at' => now(),
        ]);

        PaymentTransaction::create([
            'gateway' => 'khalti',
            'status' => PaymentStatus::COMPLETED,
            'amount' => 200,
            'merchant_reference_id' => 'completed-1',
            'initiated_at' => now(),
        ]);

        $failed = PaymentTransaction::failed()->get();

        $this->assertCount(1, $failed);
        $this->assertTrue($failed->first()->isFailed());
    }

    public function test_scope_pending_includes_pending_and_processing()
    {
        PaymentTransaction::create([
            'gateway' => 'khalti',
            'status' => PaymentStatus::PENDING,
            'amount' => 100,
            'merchant_reference_id' => 'pending-1',
            'initiated_at' => now(),
        ]);

        PaymentTransaction::create([
            'gateway' => 'khalti',
            'status' => PaymentStatus::PROCESSING,
            'amount' => 200,
            'merchant_reference_id' => 'processing-1',
            'initiated_at' => now(),
        ]);

        PaymentTransaction::create([
            'gateway' => 'khalti',
            'status' => PaymentStatus::COMPLETED,
            'amount' => 300,
            'merchant_reference_id' => 'completed-1',
            'initiated_at' => now(),
        ]);

        $pending = PaymentTransaction::pending()->get();

        $this->assertCount(2, $pending);
    }

    public function test_scope_by_reference()
    {
        PaymentTransaction::create([
            'gateway' => 'khalti',
            'status' => PaymentStatus::PENDING,
            'amount' => 100,
            'merchant_reference_id' => 'unique-ref-123',
            'initiated_at' => now(),
        ]);

        $found = PaymentTransaction::byReference('unique-ref-123')->first();

        $this->assertNotNull($found);
        $this->assertEquals('unique-ref-123', $found->merchant_reference_id);
    }

    public function test_scope_for_payable()
    {
        PaymentTransaction::create([
            'gateway' => 'khalti',
            'status' => PaymentStatus::PENDING,
            'amount' => 100,
            'merchant_reference_id' => 'payable-1',
            'payable_type' => 'orders',
            'payable_id' => 1,
            'initiated_at' => now(),
        ]);

        PaymentTransaction::create([
            'gateway' => 'khalti',
            'status' => PaymentStatus::PENDING,
            'amount' => 200,
            'merchant_reference_id' => 'payable-2',
            'payable_type' => 'orders',
            'payable_id' => 2,
            'initiated_at' => now(),
        ]);

        $payments = PaymentTransaction::forPayable('orders', 1)->get();

        $this->assertCount(1, $payments);
        $this->assertEquals(1, $payments->first()->payable_id);
    }

    // ========== Status Helper Methods ==========

    public function test_is_completed_returns_true_for_completed_status()
    {
        $transaction = PaymentTransaction::create([
            'gateway' => 'khalti',
            'status' => PaymentStatus::COMPLETED,
            'amount' => 100,
            'merchant_reference_id' => 'test-1',
            'initiated_at' => now(),
        ]);

        $this->assertTrue($transaction->isCompleted());
        $this->assertFalse($transaction->isFailed());
        $this->assertFalse($transaction->isPending());
    }

    public function test_is_failed_returns_true_for_failed_status()
    {
        $transaction = PaymentTransaction::create([
            'gateway' => 'khalti',
            'status' => PaymentStatus::FAILED,
            'amount' => 100,
            'merchant_reference_id' => 'test-1',
            'initiated_at' => now(),
        ]);

        $this->assertTrue($transaction->isFailed());
        $this->assertFalse($transaction->isCompleted());
    }

    public function test_is_pending_returns_true_for_pending_status()
    {
        $transaction = PaymentTransaction::create([
            'gateway' => 'khalti',
            'status' => PaymentStatus::PENDING,
            'amount' => 100,
            'merchant_reference_id' => 'test-1',
            'initiated_at' => now(),
        ]);

        $this->assertTrue($transaction->isPending());
    }

    // ========== State Transition Methods ==========

    public function test_mark_as_processing_updates_status_and_timestamp()
    {
        $transaction = PaymentTransaction::create([
            'gateway' => 'khalti',
            'status' => PaymentStatus::PENDING,
            'amount' => 100,
            'merchant_reference_id' => 'test-1',
            'initiated_at' => now(),
        ]);

        $transaction->markAsProcessing();

        $this->assertEquals(PaymentStatus::PROCESSING, $transaction->fresh()->status);
        $this->assertNotNull($transaction->fresh()->verified_at);
    }

    public function test_mark_as_completed_updates_status_and_timestamp()
    {
        $transaction = PaymentTransaction::create([
            'gateway' => 'khalti',
            'status' => PaymentStatus::PROCESSING,
            'amount' => 100,
            'merchant_reference_id' => 'test-1',
            'initiated_at' => now(),
        ]);

        $transaction->markAsCompleted();

        $this->assertEquals(PaymentStatus::COMPLETED, $transaction->fresh()->status);
        $this->assertNotNull($transaction->fresh()->completed_at);
    }

    public function test_mark_as_failed_updates_status_and_timestamp()
    {
        $transaction = PaymentTransaction::create([
            'gateway' => 'khalti',
            'status' => PaymentStatus::PENDING,
            'amount' => 100,
            'merchant_reference_id' => 'test-1',
            'initiated_at' => now(),
        ]);

        $transaction->markAsFailed();

        $this->assertEquals(PaymentStatus::FAILED, $transaction->fresh()->status);
        $this->assertNotNull($transaction->fresh()->failed_at);
    }

    public function test_mark_as_cancelled_updates_status()
    {
        $transaction = PaymentTransaction::create([
            'gateway' => 'khalti',
            'status' => PaymentStatus::PENDING,
            'amount' => 100,
            'merchant_reference_id' => 'test-1',
            'initiated_at' => now(),
        ]);

        $transaction->markAsCancelled();

        $this->assertEquals(PaymentStatus::CANCELLED, $transaction->fresh()->status);
    }

    public function test_payable_relationship_is_morph_to()
    {
        $transaction = PaymentTransaction::create([
            'gateway' => 'khalti',
            'status' => PaymentStatus::PENDING,
            'amount' => 100,
            'merchant_reference_id' => 'test-1',
            'payable_type' => 'orders',
            'payable_id' => 123,
            'initiated_at' => now(),
        ]);

        $this->assertEquals('orders', $transaction->payable_type);
        $this->assertEquals(123, $transaction->payable_id);
    }
}
