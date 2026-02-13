<?php

declare(strict_types=1);

namespace JaapTech\NepaliPayment\Tests\Unit\Enums;

use JaapTech\NepaliPayment\Enums\PaymentStatus;
use PHPUnit\Framework\TestCase;

class PaymentStatusTest extends TestCase
{
    public function test_payment_status_has_all_expected_cases()
    {
        $cases = PaymentStatus::cases();
        $values = array_map(fn($case) => $case->value, $cases);

        $this->assertContains('pending', $values);
        $this->assertContains('processing (paid)', $values);
        $this->assertContains('completed', $values);
        $this->assertContains('failed', $values);
        $this->assertContains('refunded', $values);
        $this->assertContains('cancelled', $values);
    }

    public function test_pending_status_has_correct_value()
    {
        $this->assertEquals('pending', PaymentStatus::PENDING->value);
    }

    public function test_processing_status_has_correct_value()
    {
        $this->assertEquals('processing (paid)', PaymentStatus::PROCESSING->value);
    }

    public function test_completed_status_has_correct_value()
    {
        $this->assertEquals('completed', PaymentStatus::COMPLETED->value);
    }

    public function test_failed_status_has_correct_value()
    {
        $this->assertEquals('failed', PaymentStatus::FAILED->value);
    }

    public function test_refunded_status_has_correct_value()
    {
        $this->assertEquals('refunded', PaymentStatus::REFUNDED->value);
    }

    public function test_cancelled_status_has_correct_value()
    {
        $this->assertEquals('cancelled', PaymentStatus::CANCELLED->value);
    }

    public function test_can_create_status_from_string()
    {
        $status = PaymentStatus::from('completed');
        $this->assertEquals(PaymentStatus::COMPLETED, $status);
    }

    public function test_creating_from_invalid_string_throws_exception()
    {
        $this->expectException(\ValueError::class);
        PaymentStatus::from('invalid_status');
    }

    public function test_try_from_returns_null_for_invalid_string()
    {
        $status = PaymentStatus::tryFrom('invalid_status');
        $this->assertNull($status);
    }

    public function test_try_from_returns_enum_for_valid_string()
    {
        $status = PaymentStatus::tryFrom('pending');
        $this->assertEquals(PaymentStatus::PENDING, $status);
    }
}
