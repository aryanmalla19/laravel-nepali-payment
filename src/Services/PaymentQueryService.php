<?php

declare(strict_types=1);

namespace JaapTech\NepaliPayment\Services;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\Eloquent\Builder;
use JaapTech\NepaliPayment\Enums\PaymentStatus;
use JaapTech\NepaliPayment\Exceptions\DatabaseException;
use JaapTech\NepaliPayment\Models\Payment;

class PaymentQueryService
{
    public function __construct(protected Repository $config) {}

    /**
     * Find a payment by reference ID.
     */
    public function findByReference(string $referenceId): ?Payment
    {
        if (! $this->isDatabaseEnabled()) {
            return null;
        }

        return Payment::byReference($referenceId)->first();
    }

    /**
     * Find a payment by gateway transaction ID.
     */
    public function findByGatewayId(string $gatewayTransactionId): ?Payment
    {
        if (! $this->isDatabaseEnabled()) {
            return null;
        }

        return Payment::byGatewayTransactionId($gatewayTransactionId)->first();
    }

    /**
     * Get all payments by status.
     */
    public function getByStatus(PaymentStatus|string $status): Builder
    {
        if (! $this->isDatabaseEnabled()) {
            throw DatabaseException::disabled();
        }

        return Payment::byStatus($status);
    }

    /**
     * Get all payments by gateway.
     */
    public function getByGateway(string $gateway): Builder
    {
        if (! $this->isDatabaseEnabled()) {
            throw DatabaseException::disabled();
        }

        return Payment::byGateway($gateway);
    }

    /**
     * Get all payments for a specific payable model.
     */
    public function getForPayable(string $payableType, int|string $payableId): Builder
    {
        if (! $this->isDatabaseEnabled()) {
            throw DatabaseException::disabled();
        }

        return Payment::forPayable($payableType, $payableId);
    }

    /**
     * Check if database integration is enabled.
     */
    protected function isDatabaseEnabled(): bool
    {
        return (bool) $this->config->get('nepali-payment.database.enabled', false);
    }
}
