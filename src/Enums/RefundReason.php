<?php

namespace JaapTech\NepaliPayment\Enums;

enum RefundReason: string
{
    case USER_REQUEST = 'user_request';
    case DUPLICATE = 'duplicate';
    case ERROR = 'error';
    case OTHER = 'other';

    /**
     * Get human-readable label for the refund reason.
     */
    public function label(): string
    {
        return match ($this) {
            self::USER_REQUEST => 'User Request',
            self::DUPLICATE => 'Duplicate Payment',
            self::ERROR => 'System Error',
            self::OTHER => 'Other',
        };
    }

    /**
     * Get description for the refund reason.
     */
    public function description(): string
    {
        return match ($this) {
            self::USER_REQUEST => 'Customer requested a refund',
            self::DUPLICATE => 'Payment was processed twice',
            self::ERROR => 'Payment failed due to system error',
            self::OTHER => 'Other reason for refund',
        };
    }
}
