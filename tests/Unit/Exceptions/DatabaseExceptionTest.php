<?php

declare(strict_types=1);

namespace JaapTech\NepaliPayment\Tests\Unit\Exceptions;

use JaapTech\NepaliPayment\Exceptions\DatabaseException;
use PHPUnit\Framework\TestCase;

class DatabaseExceptionTest extends TestCase
{
    public function test_create_failed_returns_exception_with_correct_message()
    {
        $exception = DatabaseException::createFailed('KHALTI', 'Connection timeout');

        $this->assertInstanceOf(DatabaseException::class, $exception);
        $this->assertStringContainsString('KHALTI', $exception->getMessage());
        $this->assertStringContainsString('Connection timeout', $exception->getMessage());
    }

    public function test_update_failed_returns_exception_with_correct_message()
    {
        $exception = DatabaseException::updateFailed('123-uuid', 'Record not found');

        $this->assertInstanceOf(DatabaseException::class, $exception);
        $this->assertStringContainsString('123-uuid', $exception->getMessage());
        $this->assertStringContainsString('Record not found', $exception->getMessage());
    }

    public function test_refund_failed_returns_exception_with_correct_message()
    {
        $exception = DatabaseException::refundFailed('456-uuid', 'Refund error');

        $this->assertInstanceOf(DatabaseException::class, $exception);
        $this->assertStringContainsString('456-uuid', $exception->getMessage());
        $this->assertStringContainsString('Refund error', $exception->getMessage());
    }

    public function test_disabled_returns_exception_with_correct_message()
    {
        $exception = DatabaseException::disabled();

        $this->assertInstanceOf(DatabaseException::class, $exception);
        $this->assertStringContainsString('Database integration is not enabled. Set NEPALI_PAYMENT_DATABASE_ENABLED=true in .env', $exception->getMessage());
    }

    public function test_exception_extends_base_exception()
    {
        $exception = DatabaseException::createFailed('TEST', 'Test message');

        $this->assertInstanceOf(\Exception::class, $exception);
    }

    public function test_exception_has_correct_code()
    {
        $exception = DatabaseException::createFailed('TEST', 'Test message');

        $this->assertEquals(0, $exception->getCode());
    }
}
