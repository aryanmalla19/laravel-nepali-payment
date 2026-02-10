<?php

declare(strict_types=1);

namespace JaapTech\NepaliPayment\Services;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\Eloquent\Builder;
use JaapTech\NepaliPayment\Enums\PaymentStatus;
use JaapTech\NepaliPayment\Exceptions\DatabaseException;
use JaapTech\NepaliPayment\Models\PaymentTransaction;

class PaymentTransactionQueryService
{
    public function __construct(protected Repository $config) {}

    /**
     * Find a payment by reference ID.
     *
     * @throws DatabaseException
     */
    public function findByReference(string $referenceId): ?PaymentTransaction
    {
        if (! $this->isDatabaseEnabled()) {
            throw DatabaseException::disabled();
        }

        return PaymentTransaction::byReference($referenceId)->first();
    }

    /**
     * Get all payments by status.
     *
     * @throws DatabaseException
     */
    public function getByStatus(PaymentStatus|string $status): Builder
    {
        if (! $this->isDatabaseEnabled()) {
            throw DatabaseException::disabled();
        }

        return PaymentTransaction::byStatus($status);
    }

    /**
     * Get all payments by gateway.
     *
     * @throws DatabaseException
     */
    public function getByGateway(string $gateway): Builder
    {
        if (! $this->isDatabaseEnabled()) {
            throw DatabaseException::disabled();
        }

        return PaymentTransaction::byGateway($gateway);
    }

    /**
     * Get all payments for a specific payable model.
     *
     * @return Builder<PaymentTransaction>
     *
     * @throws DatabaseException
     */
    public function getForPayable(string $payableType, int|string $payableId): Builder
    {
        if (! $this->isDatabaseEnabled()) {
            throw DatabaseException::disabled();
        }

        return PaymentTransaction::forPayable($payableType, $payableId);
    }

    /**
     * Check if database integration is enabled.
     */
    protected function isDatabaseEnabled(): bool
    {
        return (bool) $this->config->get('nepali-payment.database.enabled', false);
    }
}
