<?php

declare(strict_types=1);

namespace JaapTech\NepaliPayment\Services;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\Eloquent\Builder;
use JaapTech\NepaliPayment\Enums\PaymentStatus;
use JaapTech\NepaliPayment\Enums\RefundReason;
use JaapTech\NepaliPayment\Models\Payment;
use JaapTech\NepaliPayment\Models\PaymentRefund;
use Kbk\NepaliPaymentGateway\Epay\ConnectIps;
use Kbk\NepaliPaymentGateway\Epay\Esewa;
use Kbk\NepaliPaymentGateway\Epay\Khalti;

/**
 * PaymentManager - Orchestrator for payment operations
 *
 * Manages gateway access and delegates database operations to specialized services.
 * Wraps gateways with interceptors for automatic payment logging when enabled.
 */
class PaymentManager
{
    protected array $drivers = [];

    public function __construct(
        protected Repository $config,
        protected PaymentService $paymentService,
        protected RefundService $refundService,
        protected PaymentQueryService $queryService
    ) {}

    // ============= Gateway Access with Auto-Logging =============

    /**
     * Get eSewa gateway instance with auto-logging interceptor.
     */
    public function esewa(): Esewa
    {
        $esewa = $this->drivers['esewa'] ??= $this->createEsewa();

        return $this->wrapWithInterceptor($esewa, 'esewa');
    }

    /**
     * Get Khalti gateway instance with auto-logging interceptor.
     */
    public function khalti(): Khalti
    {
        $khalti = $this->drivers['khalti'] ??= $this->createKhalti();

        return $this->wrapWithInterceptor($khalti, 'khalti');
    }

    /**
     * Get ConnectIps gateway instance with auto-logging interceptor.
     */
    public function connectips(): ConnectIps
    {
        $connectips = $this->drivers['connectips'] ??= $this->createConnectIps();

        return $this->wrapWithInterceptor($connectips, 'connectips');
    }

    /**
     * Wrap gateway with interceptor if database is enabled.
     */
    private function wrapWithInterceptor(object $gateway, string $gatewayName): object
    {
        if (! $this->isDatabaseEnabled()) {
            return $gateway;
        }

        return new GatewayPaymentInterceptor(
            $gateway,
            $this->paymentService,
            $gatewayName,
            $this->config
        );
    }

    // ============= Delegated Payment Operations =============

    /**
     * Create a new payment record in the database.
     */
    public function createPayment(
        string $gateway,
        float $amount,
        array $paymentData = [],
        ?string $payableType = null,
        ?int $payableId = null,
        array $metadata = []
    ): Payment {
        return $this->paymentService->createPayment(
            $gateway,
            $amount,
            $paymentData,
            $payableType,
            $payableId,
            $metadata
        );
    }

    /**
     * Record payment verification in database.
     */
    public function recordPaymentVerification(
        Payment $payment,
        array $verificationData,
        bool $isSuccess = true
    ): void {
        $this->paymentService->recordPaymentVerification(
            $payment,
            $verificationData,
            $isSuccess
        );
    }

    /**
     * Mark a payment as completed.
     */
    public function completePayment(
        Payment $payment,
        ?string $gatewayTransactionId = null,
        array $responseData = []
    ): void {
        $this->paymentService->completePayment(
            $payment,
            $gatewayTransactionId,
            $responseData
        );
    }

    /**
     * Mark a payment as failed.
     */
    public function failPayment(
        Payment $payment,
        ?string $reason = null
    ): void {
        $this->paymentService->failPayment($payment, $reason);
    }

    /**
     * Find a payment by reference ID.
     */
    public function findPaymentByReference(string $referenceId): ?Payment
    {
        return $this->paymentService->findByReference($referenceId);
    }

    /**
     * Find a payment by gateway transaction ID.
     */
    public function findPaymentByGatewayId(string $gatewayTransactionId): ?Payment
    {
        return $this->paymentService->findByGatewayId($gatewayTransactionId);
    }

    // ============= Delegated Refund Operations =============

    /**
     * Create a refund record for a payment.
     */
    public function createRefund(
        Payment $payment,
        float $refundAmount,
        RefundReason|string $reason = RefundReason::USER_REQUEST,
        ?string $notes = null,
        ?int $requestedBy = null
    ): PaymentRefund {
        return $this->refundService->createRefund(
            $payment,
            $refundAmount,
            $reason,
            $notes,
            $requestedBy
        );
    }

    /**
     * Process a refund with a gateway.
     */
    public function processRefund(
        PaymentRefund $refund,
        array $responseData = [],
        bool $isSuccess = true
    ): void {
        $this->refundService->processRefund($refund, $responseData, $isSuccess);
    }

    // ============= Delegated Query Operations =============

    /**
     * Get all payments by status.
     */
    public function getPaymentsByStatus(PaymentStatus|string $status): Builder
    {
        return $this->queryService->getByStatus($status);
    }

    /**
     * Get all payments by gateway.
     */
    public function getPaymentsByGateway(string $gateway): Builder
    {
        return $this->queryService->getByGateway($gateway);
    }

    /**
     * Get all payments for a specific payable model.
     */
    public function getPaymentsForPayable(string $payableType, int|string $payableId): Builder
    {
        return $this->queryService->getForPayable($payableType, $payableId);
    }

    // ============= Private Helpers =============

    /**
     * Create eSewa gateway instance.
     */
    protected function createEsewa(): Esewa
    {
        $config = $this->config->get('nepali-payment.esewa');
        $this->ensureConfig('esewa', ['product_code', 'secret_key']);

        return new Esewa(
            $config['product_code'],
            $config['secret_key']
        );
    }

    /**
     * Create Khalti gateway instance.
     */
    protected function createKhalti(): Khalti
    {
        $config = $this->config->get('nepali-payment.khalti');
        $this->ensureConfig('khalti', ['secret_key']);

        return new Khalti(
            $config['secret_key'],
            $config['environment']
        );
    }

    /**
     * Create ConnectIps gateway instance.
     */
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

    /**
     * Ensure required configuration keys are set.
     */
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

    /**
     * Check if database integration is enabled.
     */
    protected function isDatabaseEnabled(): bool
    {
        return (bool) $this->config->get('nepali-payment.database.enabled', false);
    }
}
