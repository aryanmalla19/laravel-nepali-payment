<?php

declare(strict_types=1);

namespace JaapTech\NepaliPayment\Tests\Unit\Exceptions;

use JaapTech\NepaliPayment\Exceptions\PaymentException;
use PHPUnit\Framework\TestCase;

class PaymentExceptionTest extends TestCase
{
    public function test_cannot_be_refunded_returns_exception_with_status()
    {
        $exception = PaymentException::cannotBeRefunded('pending');

        $this->assertInstanceOf(PaymentException::class, $exception);
        $this->assertStringContainsString('pending', $exception->getMessage());
        $this->assertStringContainsString('refunded', $exception->getMessage());
    }

    public function test_insufficient_refundable_amount_returns_exception_with_amounts()
    {
        $exception = PaymentException::insufficientRefundableAmount(500, 300);

        $this->assertInstanceOf(PaymentException::class, $exception);
        $this->assertStringContainsString('500', $exception->getMessage());
        $this->assertStringContainsString('300', $exception->getMessage());
    }

    public function test_exception_extends_base_exception()
    {
        $exception = PaymentException::cannotBeRefunded('test');

        $this->assertInstanceOf(\Exception::class, $exception);
    }

    public function test_cannot_be_refunded_with_completed_status()
    {
        $exception = PaymentException::cannotBeRefunded('completed');

        $this->assertStringContainsString('completed', $exception->getMessage());
    }

    public function test_cannot_be_refunded_with_failed_status()
    {
        $exception = PaymentException::cannotBeRefunded('failed');

        $this->assertStringContainsString('failed', $exception->getMessage());
    }
}
