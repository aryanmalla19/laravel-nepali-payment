<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use JaapTech\NepaliPayment\Exceptions\DatabaseException;
use JaapTech\NepaliPayment\Facades\NepaliPayment;
use JaapTech\NepaliPayment\Models\PaymentRefund;
use JaapTech\NepaliPayment\Models\PaymentTransaction;

if (! function_exists('nepali_payment_enabled')) {
    /**
     * Check if database integration is enabled for Nepali Payment package.
     */
    function nepali_payment_enabled(): bool
    {
        return config('nepali-payment.database.enabled', false);
    }
}

if (! function_exists('nepali_payment_find')) {
    /**
     * Find a payment by reference ID.
     */
    function nepali_payment_find(string $referenceId): ?PaymentTransaction
    {
        if (! nepali_payment_enabled()) {
            return null;
        }

        return PaymentTransaction::byReference($referenceId)->first();
    }
}

if (! function_exists('nepali_payment_get_by_status')) {
    /**
     * Get all payments by status.
     */
    function nepali_payment_get_by_status(string $status)
    {
        if (! nepali_payment_enabled()) {
            throw new \RuntimeException('Database integration is not enabled');
        }

        return PaymentTransaction::byStatus($status);
    }
}

if (! function_exists('nepali_payment_get_by_gateway')) {
    /**
     * Get all payments by gateway.
     */
    function nepali_payment_get_by_gateway(string $gateway)
    {
        if (! nepali_payment_enabled()) {
            throw new \RuntimeException('Database integration is not enabled');
        }

        return PaymentTransaction::byGateway($gateway);
    }
}
