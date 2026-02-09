<?php

declare(strict_types=1);

namespace JaapTech\NepaliPayment\Exceptions;

class DatabaseException extends \Exception
{
    public static function createFailed(string $gateway, string $reason): self
    {
        return new self(
            "Failed to create payment record for gateway '{$gateway}'. Reason: {$reason}"
        );
    }

    public static function updateFailed(string $paymentId, string $reason): self
    {
        return new self(
            "Failed to update payment record '{$paymentId}'. Reason: {$reason}"
        );
    }

    public static function notFound(string $identifier): self
    {
        return new self("Payment not found with identifier: {$identifier}");
    }

    public static function disabled(): self
    {
        return new self('Database integration is not enabled. Set NEPALI_PAYMENT_DATABASE_ENABLED=true in .env');
    }
}
