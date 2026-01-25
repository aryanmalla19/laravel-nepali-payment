<?php

declare(strict_types=1);

use JaapTech\NepaliPayment\Facades\NepaliPayment;
use JaapTech\NepaliPayment\Models\Payment;
use JaapTech\NepaliPayment\Models\PaymentRefund;

if (!function_exists('nepali_payment_enabled')) {
    /**
     * Check if database integration is enabled for Nepali Payment package.
     */
    function nepali_payment_enabled(): bool
    {
        return config('nepali-payment.database.enabled', false);
    }
}

if (!function_exists('nepali_payment_find')) {
    /**
     * Find a payment by reference ID.
     */
    function nepali_payment_find(string $referenceId): ?Payment
    {
        if (!nepali_payment_enabled()) {
            return null;
        }

        return Payment::byReference($referenceId)->first();
    }
}

if (!function_exists('nepali_payment_find_by_gateway_id')) {
    /**
     * Find a payment by gateway transaction ID.
     */
    function nepali_payment_find_by_gateway_id(string $gatewayTransactionId): ?Payment
    {
        if (!nepali_payment_enabled()) {
            return null;
        }

        return Payment::byGatewayTransactionId($gatewayTransactionId)->first();
    }
}

if (!function_exists('nepali_payment_create')) {
    /**
     * Create a new payment record.
     */
    function nepali_payment_create(
        string $gateway,
        float $amount,
        array $paymentData = [],
        ?string $payableType = null,
        ?int|string $payableId = null,
        array $metadata = []
    ): Payment {
        return NepaliPayment::createPayment(
            $gateway,
            $amount,
            $paymentData,
            $payableType,
            $payableId,
            $metadata
        );
    }
}

if (!function_exists('nepali_payment_refund')) {
    /**
     * Create a refund for a payment.
     */
    function nepali_payment_refund(
        Payment $payment,
        float $refundAmount,
        string $reason = 'user_request',
        ?string $notes = null,
        ?int|string $requestedBy = null
    ): PaymentRefund {
        return NepaliPayment::createRefund(
            $payment,
            $refundAmount,
            $reason,
            $notes,
            $requestedBy
        );
    }
}

if (!function_exists('nepali_payment_get_by_status')) {
    /**
     * Get all payments by status.
     */
    function nepali_payment_get_by_status(string $status)
    {
        if (!nepali_payment_enabled()) {
            throw new \RuntimeException('Database integration is not enabled');
        }

        return Payment::byStatus($status);
    }
}

if (!function_exists('nepali_payment_get_by_gateway')) {
    /**
     * Get all payments by gateway.
     */
    function nepali_payment_get_by_gateway(string $gateway)
    {
        if (!nepali_payment_enabled()) {
            throw new \RuntimeException('Database integration is not enabled');
        }

        return Payment::byGateway($gateway);
    }
}
