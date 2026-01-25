<?php

declare(strict_types=1);

namespace JaapTech\NepaliPayment\Services;

use Illuminate\Contracts\Config\Repository;
use JaapTech\NepaliPayment\Enums\RefundReason;
use JaapTech\NepaliPayment\Exceptions\DatabaseException;
use JaapTech\NepaliPayment\Exceptions\PaymentException;
use JaapTech\NepaliPayment\Models\Payment;
use JaapTech\NepaliPayment\Models\PaymentRefund;

class RefundService
{
    public function __construct(
        protected Repository $config,
        protected PaymentService $paymentService
    ) {}

    /**
     * Create a refund record for a payment.
     */
    public function createRefund(
        Payment $payment,
        float $refundAmount,
        RefundReason|string $reason = RefundReason::USER_REQUEST,
        ?string $notes = null,
        int|string|null $requestedBy = null
    ): PaymentRefund {
        if (!$this->isDatabaseEnabled()) {
            throw DatabaseException::disabled();
        }

        try {
            // Validate payment can be refunded
            if (!$payment->canBeRefunded()) {
                throw PaymentException::cannotBeRefunded($payment->status->value);
            }

            // Validate refund amount
            if ($refundAmount > $payment->getRemainingRefundableAmount()) {
                throw PaymentException::insufficientRefundableAmount(
                    $refundAmount,
                    $payment->getRemainingRefundableAmount()
                );
            }

            // Convert reason to enum if string
            $refundReasonEnum = $reason instanceof RefundReason 
                ? $reason 
                : RefundReason::tryFrom($reason) ?? RefundReason::OTHER;

            $refund = PaymentRefund::create([
                'payment_id' => $payment->id,
                'refund_amount' => $refundAmount,
                'refund_reason' => $refundReasonEnum,
                'refund_status' => 'pending',
                'notes' => $notes,
                'requested_by' => $requestedBy,
                'requested_at' => now(),
            ]);

            return $refund;
        } catch (PaymentException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw DatabaseException::refundFailed($payment->id, $e->getMessage());
        }
    }

    /**
     * Process a refund with a gateway.
     */
    public function processRefund(
        PaymentRefund $refund,
        array $responseData = [],
        bool $isSuccess = true
    ): void {
        if (!$this->isDatabaseEnabled()) {
            return;
        }

        try {
            if ($isSuccess) {
                $refund->markAsCompleted($responseData['gateway_refund_id'] ?? null);
            } else {
                $refund->markAsFailed($responseData['error'] ?? 'Refund processing failed');
            }

            if (!empty($responseData)) {
                $refund->update(['gateway_response' => $responseData]);
            }
        } catch (\Exception $e) {
            throw DatabaseException::updateFailed($refund->id, $e->getMessage());
        }
    }

    /**
     * Check if database integration is enabled.
     */
    protected function isDatabaseEnabled(): bool
    {
        return (bool) $this->config->get('nepali-payment.database.enabled', false);
    }
}
