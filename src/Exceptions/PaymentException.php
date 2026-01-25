<?php

declare(strict_types=1);

namespace JaapTech\NepaliPayment\Exceptions;

class PaymentException extends \Exception
{
    public static function cannotBeRefunded(string $status): self
    {
        return new self(
            "Payment with status '{$status}' cannot be refunded. Only completed payments can be refunded."
        );
    }

    public static function insufficientRefundableAmount(float $requested, float $available): self
    {
        return new self(
            "Refund amount ({$requested}) exceeds remaining refundable amount ({$available})"
        );
    }

    public static function invalidRefundReason(string $reason): self
    {
        return new self("Invalid refund reason: {$reason}");
    }
}
