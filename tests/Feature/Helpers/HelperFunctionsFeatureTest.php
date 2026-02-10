<?php

declare(strict_types=1);

namespace JaapTech\NepaliPayment\Tests\Feature\Helpers;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use JaapTech\NepaliPayment\Enums\PaymentStatus;
use JaapTech\NepaliPayment\Models\PaymentTransaction;
use JaapTech\NepaliPayment\Tests\TestCase;

class HelperFunctionsFeatureTest extends TestCase
{
    use RefreshDatabase;

    // ========== nepali_payment_enabled() Feature Tests ==========

    public function test_helper_returns_false_when_database_disabled()
    {
        Config::set('nepali-payment.database.enabled', false);

        $this->assertFalse(nepali_payment_enabled());
    }

    public function test_helper_returns_true_when_database_enabled()
    {
        Config::set('nepali-payment.database.enabled', true);

        $this->assertTrue(nepali_payment_enabled());
    }

    // ========== nepali_payment_find() Feature Tests ==========

    public function test_helper_finds_payment_by_reference()
    {
        Config::set('nepali-payment.database.enabled', true);

        $payment = PaymentTransaction::create([
            'gateway' => 'khalti',
            'status' => PaymentStatus::PENDING,
            'amount' => 1000,
            'merchant_reference_id' => 'ref-123',
            'initiated_at' => now(),
        ]);

        $found = nepali_payment_find('ref-123');

        $this->assertNotNull($found);
        $this->assertEquals($payment->id, $found->id);
    }

    public function test_helper_returns_null_when_payment_not_found()
    {
        Config::set('nepali-payment.database.enabled', true);

        $found = nepali_payment_find('non-existent');

        $this->assertNull($found);
    }

    public function test_helper_returns_null_when_database_disabled()
    {
        Config::set('nepali-payment.database.enabled', false);

        $found = nepali_payment_find('ref-123');

        $this->assertNull($found);
    }

    // ========== nepali_payment_get_by_status() Feature Tests ==========

    public function test_helper_gets_payments_by_status()
    {
        Config::set('nepali-payment.database.enabled', true);

        PaymentTransaction::create([
            'gateway' => 'khalti',
            'status' => PaymentStatus::COMPLETED,
            'amount' => 1000,
            'merchant_reference_id' => 'ref-1',
            'initiated_at' => now(),
        ]);

        PaymentTransaction::create([
            'gateway' => 'khalti',
            'status' => PaymentStatus::PENDING,
            'amount' => 2000,
            'merchant_reference_id' => 'ref-2',
            'initiated_at' => now(),
        ]);

        $completed = nepali_payment_get_by_status('completed')->get();

        $this->assertCount(1, $completed);
        $this->assertEquals('completed', $completed->first()->status->value);
    }

    public function test_helper_throws_exception_when_get_by_status_and_database_disabled()
    {
        Config::set('nepali-payment.database.enabled', false);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Database integration is not enabled');

        nepali_payment_get_by_status('completed');
    }

    // ========== nepali_payment_get_by_gateway() Feature Tests ==========

    public function test_helper_gets_payments_by_gateway()
    {
        Config::set('nepali-payment.database.enabled', true);

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

        $khaltiPayments = nepali_payment_get_by_gateway('khalti')->get();

        $this->assertCount(1, $khaltiPayments);
        $this->assertEquals('khalti', $khaltiPayments->first()->gateway);
    }

    public function test_helper_throws_exception_when_get_by_gateway_and_database_disabled()
    {
        Config::set('nepali-payment.database.enabled', false);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Database integration is not enabled');

        nepali_payment_get_by_gateway('khalti');
    }

    // ========== Integration Tests ==========

    public function test_full_payment_lifecycle_using_helpers()
    {
        Config::set('nepali-payment.database.enabled', true);

        // Create payment
        $payment = PaymentTransaction::create([
            'gateway' => 'khalti',
            'status' => PaymentStatus::PENDING,
            'amount' => 1000,
            'merchant_reference_id' => 'pidx-123',
            'gateway_payload' => ['amount' => 1000],
            'gateway_response' => ['pidx' => 'pidx-123'],
            'initiated_at' => now(),
        ]);

        $this->assertNotNull($payment);

        // Find payment
        $found = nepali_payment_find('pidx-123');
        $this->assertEquals($payment->id, $found->id);

        // Get by status
        $pending = nepali_payment_get_by_status('pending')->get();
        $this->assertCount(1, $pending);

        // Get by gateway
        $khaltiPayments = nepali_payment_get_by_gateway('khalti')->get();
        $this->assertCount(1, $khaltiPayments);
    }
}
