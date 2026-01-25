<?php

declare(strict_types=1);

namespace JaapTech\NepaliPayment\Services;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Support\Str;
use JaapTech\NepaliPayment\Enums\PaymentStatus;
use JaapTech\NepaliPayment\Enums\RefundReason;
use JaapTech\NepaliPayment\Models\Payment;
use JaapTech\NepaliPayment\Models\PaymentRefund;
use Kbk\NepaliPaymentGateway\Epay\ConnectIps;
use Kbk\NepaliPaymentGateway\Epay\Esewa;
use Kbk\NepaliPaymentGateway\Epay\Khalti;

class PaymentManager
{
    protected array $drivers = [];

    public function __construct(protected Repository $config) {}

    public function esewa(): Esewa
    {
        return $this->drivers['esewa'] ??= $this->createEsewa();
    }

    public function khalti(): Khalti
    {
        return $this->drivers['khalti'] ??= $this->createKhalti();
    }

    public function connectips(): ConnectIps
    {
        return $this->drivers['connectips'] ??= $this->createConnectIps();
    }

    /**
     * Create a new payment record in the database.
     * 
     * @param string $gateway The payment gateway (esewa, khalti, connectips)
     * @param float $amount The payment amount
     * @param array $paymentData The payment data to send to gateway
     * @param string|null $payableType The model type to associate payment with (e.g., 'App\Models\User')
     * @param int|string|null $payableId The model ID to associate payment with
     * @param array $metadata Additional metadata to store with payment
     * @return Payment
     */
    public function createPayment(
        string $gateway,
        float $amount,
        array $paymentData = [],
        ?string $payableType = null,
        ?int|string $payableId = null,
        array $metadata = []
    ): Payment {
        if (!$this->isDatabaseEnabled()) {
            throw new \RuntimeException('Database integration is not enabled. Set NEPALI_PAYMENT_DATABASE_ENABLED=true in .env');
        }

        // Generate unique reference ID if not provided
        $referenceId = $paymentData['reference_id'] ?? Str::uuid()->toString();

        $payment = Payment::create([
            'gateway' => $gateway,
            'status' => PaymentStatus::PENDING,
            'amount' => $amount,
            'currency' => $paymentData['currency'] ?? 'NPR',
            'reference_id' => $referenceId,
            'payable_type' => $payableType,
            'payable_id' => $payableId,
            'description' => $paymentData['description'] ?? null,
            'metadata' => $metadata,
            'initiated_at' => now(),
        ]);

        return $payment;
    }

    /**
     * Record payment verification in database.
     * 
     * @param Payment $payment
     * @param array $verificationData Data from gateway verification
     * @param bool $isSuccess Whether verification was successful
     * @return void
     */
    public function recordPaymentVerification(
        Payment $payment,
        array $verificationData,
        bool $isSuccess = true
    ): void {
        if (!$this->isDatabaseEnabled()) {
            return;
        }

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
    }

    /**
     * Mark a payment as completed.
     * 
     * @param Payment $payment
     * @param string|null $gatewayTransactionId The transaction ID from gateway
     * @param array $responseData Full response from gateway
     * @return void
     */
    public function completePayment(
        Payment $payment,
        ?string $gatewayTransactionId = null,
        array $responseData = []
    ): void {
        if (!$this->isDatabaseEnabled()) {
            return;
        }

        $updateData = [
            'status' => PaymentStatus::COMPLETED,
            'completed_at' => now(),
        ];

        if ($gatewayTransactionId) {
            $updateData['gateway_transaction_id'] = $gatewayTransactionId;
        }

        if (!empty($responseData)) {
            $updateData['gateway_response'] = $responseData;
        }

        $payment->update($updateData);
    }

    /**
     * Mark a payment as failed.
     * 
     * @param Payment $payment
     * @param string|null $reason Reason for failure
     * @return void
     */
    public function failPayment(
        Payment $payment,
        ?string $reason = null
    ): void {
        if (!$this->isDatabaseEnabled()) {
            return;
        }

        $payment->markAsFailed($reason);
    }

    /**
     * Create a refund record for a payment.
     * 
     * @param Payment $payment
     * @param float $refundAmount
     * @param RefundReason|string $reason
     * @param string|null $notes
     * @param int|string|null $requestedBy User ID who requested the refund
     * @return PaymentRefund
     */
    public function createRefund(
        Payment $payment,
        float $refundAmount,
        RefundReason|string $reason = RefundReason::USER_REQUEST,
        ?string $notes = null,
        ?int|string $requestedBy = null
    ): PaymentRefund {
        if (!$this->isDatabaseEnabled()) {
            throw new \RuntimeException('Database integration is not enabled. Set NEPALI_PAYMENT_DATABASE_ENABLED=true in .env');
        }

        if (!$payment->canBeRefunded()) {
            throw new \RuntimeException("Payment status '{$payment->status->value}' cannot be refunded. Only completed payments can be refunded.");
        }

        if ($refundAmount > $payment->getRemainingRefundableAmount()) {
            throw new \RuntimeException("Refund amount ({$refundAmount}) exceeds remaining refundable amount ({$payment->getRemainingRefundableAmount()})");
        }

        $refundReasonEnum = $reason instanceof RefundReason ? $reason : RefundReason::tryFrom($reason);
        if (!$refundReasonEnum) {
            $refundReasonEnum = RefundReason::OTHER;
        }

        return PaymentRefund::create([
            'payment_id' => $payment->id,
            'refund_amount' => $refundAmount,
            'refund_reason' => $refundReasonEnum,
            'refund_status' => 'pending',
            'notes' => $notes,
            'requested_by' => $requestedBy,
            'requested_at' => now(),
        ]);
    }

    /**
     * Process a refund with a gateway.
     * 
     * @param PaymentRefund $refund
     * @param array $responseData Response from gateway
     * @param bool $isSuccess Whether refund was successful
     * @return void
     */
    public function processRefund(
        PaymentRefund $refund,
        array $responseData = [],
        bool $isSuccess = true
    ): void {
        if (!$this->isDatabaseEnabled()) {
            return;
        }

        if ($isSuccess) {
            $refund->markAsCompleted($responseData['gateway_refund_id'] ?? null);
        } else {
            $refund->markAsFailed($responseData['error'] ?? 'Refund processing failed');
        }

        if (!empty($responseData)) {
            $refund->update(['gateway_response' => $responseData]);
        }
    }

    /**
     * Find a payment by reference ID.
     * 
     * @param string $referenceId
     * @return Payment|null
     */
    public function findPaymentByReference(string $referenceId): ?Payment
    {
        if (!$this->isDatabaseEnabled()) {
            return null;
        }

        return Payment::byReference($referenceId)->first();
    }

    /**
     * Find a payment by gateway transaction ID.
     * 
     * @param string $gatewayTransactionId
     * @return Payment|null
     */
    public function findPaymentByGatewayId(string $gatewayTransactionId): ?Payment
    {
        if (!$this->isDatabaseEnabled()) {
            return null;
        }

        return Payment::byGatewayTransactionId($gatewayTransactionId)->first();
    }

    /**
     * Get all payments by status.
     * 
     * @param PaymentStatus|string $status
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function getPaymentsByStatus(PaymentStatus|string $status)
    {
        if (!$this->isDatabaseEnabled()) {
            throw new \RuntimeException('Database integration is not enabled');
        }

        return Payment::byStatus($status);
    }

    /**
     * Get all payments by gateway.
     * 
     * @param string $gateway
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function getPaymentsByGateway(string $gateway)
    {
        if (!$this->isDatabaseEnabled()) {
            throw new \RuntimeException('Database integration is not enabled');
        }

        return Payment::byGateway($gateway);
    }

    /**
     * Check if database integration is enabled.
     * 
     * @return bool
     */
    protected function isDatabaseEnabled(): bool
    {
        return (bool) $this->config->get('nepali-payment.database.enabled', false);
    }

    protected function createEsewa(): Esewa
    {
        $config = $this->config->get('nepali-payment.esewa');

        $this->ensureConfig('esewa', ['product_code', 'secret_key']);

        return new Esewa(
            $config['product_code'],
            $config['secret_key']
        );
    }

    protected function createKhalti(): Khalti
    {
        $config = $this->config->get('nepali-payment.khalti');

        $this->ensureConfig('khalti', ['secret_key']);

        return new Khalti(
            $config['secret_key'],
            $config['environment']
        );
    }

    protected function createConnectIps(): ConnectIps
    {
        $config = $this->config->get('nepali-payment.connectips');

        $this->ensureConfig('connectips', [
            'merchant_id',
            'app_id',
            'app_name',
            'password',
            'private_key_path',
        ]);

        return new ConnectIps([
            'base_url' => $config['environment'] === 'test'
                ? 'https://uat.connectips.com'
                : 'https://connectips.com',
            ...$config,
        ]);
    }

    protected function ensureConfig(string $gateway, array $keys): void
    {
        foreach ($keys as $key) {
            if (empty($this->config->get("nepali-payment.{$gateway}.{$key}"))) {
                throw new \RuntimeException(
                    "Missing config for nepali-payment [{$gateway}.{$key}]"
                );
            }
        }
    }
}
