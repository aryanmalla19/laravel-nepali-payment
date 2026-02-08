<?php

declare(strict_types=1);

namespace JaapTech\NepaliPayment\Tests\Unit\Enums;

use JaapTech\NepaliPayment\Enums\RefundStatus;
use PHPUnit\Framework\TestCase;

class RefundStatusTest extends TestCase
{
    public function test_refund_status_has_all_expected_cases()
    {
        $cases = RefundStatus::cases();
        $values = array_map(fn ($case) => $case->value, $cases);

        $this->assertContains('pending', $values);
        $this->assertContains('processing', $values);
        $this->assertContains('completed', $values);
        $this->assertContains('failed', $values);
    }

    public function test_pending_status_has_correct_value()
    {
        $this->assertEquals('pending', RefundStatus::PENDING->value);
    }

    public function test_processing_status_has_correct_value()
    {
        $this->assertEquals('processing', RefundStatus::PROCESSING->value);
    }

    public function test_completed_status_has_correct_value()
    {
        $this->assertEquals('completed', RefundStatus::COMPLETED->value);
    }

    public function test_failed_status_has_correct_value()
    {
        $this->assertEquals('failed', RefundStatus::FAILED->value);
    }

    public function test_can_create_status_from_string()
    {
        $status = RefundStatus::from('completed');
        $this->assertEquals(RefundStatus::COMPLETED, $status);
    }

    public function test_creating_from_invalid_string_throws_exception()
    {
        $this->expectException(\ValueError::class);
        RefundStatus::from('invalid_status');
    }

    public function test_try_from_returns_null_for_invalid_string()
    {
        $status = RefundStatus::tryFrom('invalid_status');
        $this->assertNull($status);
    }
}
