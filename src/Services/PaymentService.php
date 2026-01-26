<?php

declare(strict_types=1);

namespace JaapTech\NepaliPayment\Services;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Support\Str;
use JaapTech\NepaliPayment\Enums\PaymentStatus;
use JaapTech\NepaliPayment\Exceptions\DatabaseException;
use JaapTech\NepaliPayment\Models\PaymentTransaction;

class PaymentService
{
    public function __construct(
        protected Repository $config,
        protected PaymentTransactionQueryService $queryService
    ) {}

    /**
     * Create a new payment record in the database.
     * @throws DatabaseException
     */
    public function createPayment(
        string $gateway,
        float $amount,
        array $paymentData = [],
        ?string $payableType = null,
        int|string|null $payableId = null,
        array $metadata = []
    ): PaymentTransaction {
        if (! $this->isDatabaseEnabled()) throw DatabaseException::disabled();

        try {
            // Generate unique reference ID if not provided
            $referenceId = $paymentData['transaction_uuid'] ?? $paymentData['reference_id'] ?? Str::uuid()->toString();

            return PaymentTransaction::create([
                'gateway' => $gateway,
                'status' => PaymentStatus::PENDING,
                'amount' => $amount,
                'currency' => $paymentData['currency'] ?? 'NPR',
                'reference_id' => $referenceId,
                'payable_type' => $payableType,
                'payable_id' => $payableId,
                'description' => $paymentData['description'] ?? null,
                'metadata' => array_merge($metadata, $paymentData),
                'initiated_at' => now(),
            ]);

        } catch (\Exception $e) {
            throw DatabaseException::createFailed($gateway, $e->getMessage());
        }
    }

    /**
     * Record payment verification in database.
     * @throws DatabaseException
     */
    public function recordPaymentVerification(
        PaymentTransaction $payment,
        array              $verificationData,
        bool               $isSuccess = true
    ): void {
        if (! $this->isDatabaseEnabled()) throw DatabaseException::disabled();

        try {
            $updateData = [
                'gateway_response' => $verificationData,
            ];

            if ($isSuccess) {
                $updateData['status'] = PaymentStatus::PROCESSING;
                $updateData['verified_at'] = now();
            } else {
                $updateData['status'] = PaymentStatus::FAILED;
                $updateData['failed_at'] = now();
            }

            $payment->update($updateData);
        } catch (\Exception $e) {
            throw DatabaseException::updateFailed($payment->id, $e->getMessage());
        }
    }

    /**
     * Mark a payment as completed.
     * @throws DatabaseException
     */
    public function completePayment(
        PaymentTransaction $payment,
        ?string            $gatewayTransactionId = null,
        array              $responseData = []
    ): void {
        if (! $this->isDatabaseEnabled()) throw DatabaseException::disabled();

        try {
            $updateData = [
                'status' => PaymentStatus::COMPLETED,
                'completed_at' => now(),
            ];

            if ($gatewayTransactionId) {
                $updateData['gateway_transaction_id'] = $gatewayTransactionId;
            }

            if (! empty($responseData)) {
                $updateData['gateway_response'] = $responseData;
            }

            $payment->update($updateData);
        } catch (\Exception $e) {
            throw DatabaseException::updateFailed($payment->id, $e->getMessage());
        }
    }

    /**
     * Mark a payment as failed.
     * @throws DatabaseException
     */
    public function failPayment(
        PaymentTransaction $payment,
        ?string            $reason = null
    ): void {
        if (! $this->isDatabaseEnabled()) throw DatabaseException::disabled();

        try {
            $payment->markAsFailed($reason);
        } catch (\Exception $e) {
            throw DatabaseException::updateFailed($payment->id, $e->getMessage());
        }
    }

    /**
     * Find a payment by reference ID.
     * @throws DatabaseException
     */
    public function findByReference(string $referenceId): ?PaymentTransaction
    {
        return $this->queryService->findByReference($referenceId);
    }

    /**
     * Find a payment by gateway transaction ID.
     * @throws DatabaseException
     */
    public function findByTransactionId(string $gatewayTransactionId): ?PaymentTransaction
    {
        return $this->queryService->findByTransactionId($gatewayTransactionId);
    }

    /**
     * Check if database integration is enabled.
     */
    protected function isDatabaseEnabled(): bool
    {
        return (bool) $this->config->get('nepali-payment.database.enabled', false);
    }
}
