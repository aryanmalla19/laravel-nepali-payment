<?php

declare(strict_types=1);

namespace JaapTech\NepaliPayment\Services;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\Eloquent\Builder;
use JaapTech\NepaliPayment\Enums\PaymentStatus;
use JaapTech\NepaliPayment\Exceptions\DatabaseException;
use JaapTech\NepaliPayment\Models\PaymentTransaction;
use JaapTech\NepaliPayment\Models\PaymentRefund;
use Kbk\NepaliPaymentGateway\Epay\ConnectIps;
use Kbk\NepaliPaymentGateway\Epay\Esewa;
use Kbk\NepaliPaymentGateway\Epay\Khalti;
use Kbk\NepaliPaymentGateway\Exceptions\InvalidPayloadException;

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
        protected PaymentTransactionQueryService $queryService
    ) {}

    // ============= Gateway Access with Auto-Logging =============

    /**
     * Get eSewa gateway instance with auto-logging interceptor.
     * @throws InvalidPayloadException
     */
    public function esewa(): GatewayPaymentInterceptor
    {
        $esewa = $this->drivers['esewa'] ??= $this->createEsewa();

        return $this->wrapWithInterceptor($esewa, 'esewa');
    }

    /**
     * Get Khalti gateway instance with auto-logging interceptor.
     * @throws InvalidPayloadException
     */
    public function khalti(): GatewayPaymentInterceptor
    {
        $khalti = $this->drivers['khalti'] ??= $this->createKhalti();

        return $this->wrapWithInterceptor($khalti, 'khalti');
    }

    /**
     * Get ConnectIps gateway instance with auto-logging interceptor.
     * @throws InvalidPayloadException
     */
    public function connectips(): GatewayPaymentInterceptor
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
    ): PaymentTransaction {
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
     * @throws DatabaseException
     */
    public function recordPaymentVerification(
        PaymentTransaction $payment,
        array              $verificationData,
        bool               $isSuccess = true
    ): void {
        $this->paymentService->recordPaymentVerification(
            $payment,
            $verificationData,
            $isSuccess
        );
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
        $this->paymentService->completePayment(
            $payment,
            $gatewayTransactionId,
            $responseData
        );
    }

    /**
     * Mark a payment as failed.
     * @throws DatabaseException
     */
    public function failPayment(
        PaymentTransaction $payment,
        ?string            $reason = null
    ): void {
        $this->paymentService->failPayment($payment, $reason);
    }

    /**
     * Find a payment by reference ID.
     * @throws DatabaseException
     */
    public function findPaymentByReference(string $referenceId): ?PaymentTransaction
    {
        return $this->paymentService->findByReference($referenceId);
    }

    /**
     * Find a payment by gateway transaction ID.
     * @throws DatabaseException
     */
    public function findPaymentByTransactionId(string $gatewayTransactionId): ?PaymentTransaction
    {
        return $this->paymentService->findByTransactionId($gatewayTransactionId);
    }

    // ============= Delegated Refund Operations =============

    /**
     * Create a refund record for a payment.
     */
    public function createRefund(
        PaymentTransaction  $payment,
        float               $refundAmount,
        ?string $reason =   null,
        ?string             $notes = null,
        ?int                $requestedBy = null
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
     * @throws DatabaseException
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
     * @throws DatabaseException
     */
    public function getPaymentsByStatus(PaymentStatus|string $status): Builder
    {
        return $this->queryService->getByStatus($status);
    }

    /**
     * Get all payments by gateway.
     * @throws DatabaseException
     */
    public function getPaymentsByGateway(string $gateway): Builder
    {
        return $this->queryService->getByGateway($gateway);
    }

    /**
     * Get all payments for a specific payable model.
     * @throws DatabaseException
     */
    public function getPaymentsForPayable(string $payableType, int|string $payableId): Builder
    {
        return $this->queryService->getForPayable($payableType, $payableId);
    }

    // ============= Private Helpers =============

    /**
     * Create eSewa gateway instance.
     * @throws InvalidPayloadException
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
     * @throws InvalidPayloadException
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
     * @throws InvalidPayloadException
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
