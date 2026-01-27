<?php

declare(strict_types=1);

namespace JaapTech\NepaliPayment\Services;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\Eloquent\Builder;
use JaapTech\NepaliPayment\Enums\NepaliPaymentGateway;
use JaapTech\NepaliPayment\Enums\PaymentStatus;
use JaapTech\NepaliPayment\Exceptions\DatabaseException;
use JaapTech\NepaliPayment\Models\PaymentTransaction;
use JaapTech\NepaliPayment\Models\PaymentRefund;
use RuntimeException;

/**
 * PaymentManager - Orchestrator for payment operations
 *
 * Manages gateway access and delegates database operations to specialized services.
 * Wraps gateways with interceptors for automatic payment logging when enabled.
 */
class PaymentManager
{
    private bool $isDatabaseEnabled;

    public function __construct(
        protected Repository $config,
        protected PaymentService $paymentService,
        protected RefundService $refundService,
        protected PaymentTransactionQueryService $queryService,
        protected GatewayFactory $gatewayFactory
    ) {
        // Cache the database enabled flag once during construction
        $this->isDatabaseEnabled = (bool) $config->get('nepali-payment.database.enabled', false);
    }

    // ============= Gateway Access with Auto-Logging =============

    /**
     * Get a gateway instance with auto-logging interceptor if database is enabled.
     *
     * @param string|NepaliPaymentGateway $gateway Gateway name or enum
     * @return object Gateway instance or intercepted gateway
     * @throws RuntimeException If gateway is not supported
     */
    public function gateway(string|NepaliPaymentGateway $gateway): object
    {
        // Validate and get gateway instance from factory
        $gatewayInstance = $this->gatewayFactory->make($gateway);

        // Wrap with interceptor if database is enabled (only once during construction)
        if ($this->isDatabaseEnabled) {
            $gatewayName = $gateway instanceof NepaliPaymentGateway
                ? $gateway->value
                : $gateway;

            return new GatewayPaymentInterceptor(
                $gatewayInstance,
                $this->paymentService,
                $gatewayName,
                $this->config
            );
        }

        return $gatewayInstance;
    }

    /**
     * Get eSewa gateway instance with auto-logging interceptor.
     */
    public function esewa(): object
    {
        return $this->gateway(NepaliPaymentGateway::ESEWA);
    }

    /**
     * Get Khalti gateway instance with auto-logging interceptor.
     */
    public function khalti(): object
    {
        return $this->gateway(NepaliPaymentGateway::KHALTI);
    }

    /**
     * Get ConnectIps gateway instance with auto-logging interceptor.
     */
    public function connectips(): object
    {
        return $this->gateway(NepaliPaymentGateway::CONNECTIPS);
    }

    // ============= Delegated Payment Operations =============

    /**
     * Create a new payment record in the database.
     *
     * @throws RuntimeException|DatabaseException If gateway is not supported
     */
    public function createPayment(
        string|NepaliPaymentGateway $gateway,
        float $amount,
        array $paymentData = [],
        ?string $payableType = null,
        ?int $payableId = null,
        array $metadata = []
    ): PaymentTransaction {
        // Validate gateway
        $gatewayName = $gateway instanceof NepaliPaymentGateway
            ? $gateway->value
            : $gateway;
        $this->validateGateway($gatewayName);

        return $this->paymentService->createPayment(
            $gatewayName,
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
     *
     * @throws RuntimeException If gateway is not supported
     */
    public function getPaymentsByGateway(string|NepaliPaymentGateway $gateway): Builder
    {
        // Validate gateway
        $gatewayName = $gateway instanceof NepaliPaymentGateway
            ? $gateway->value
            : $gateway;
        $this->validateGateway($gatewayName);

        return $this->queryService->getByGateway($gatewayName);
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
     * Validate that a gateway is supported.
     *
     * @throws RuntimeException If gateway is not supported
     */
    private function validateGateway(string $gatewayName): void
    {
        $supportedGateways = array_map(
            fn (NepaliPaymentGateway $gateway) => $gateway->value,
            NepaliPaymentGateway::cases()
        );

        if (!in_array($gatewayName, $supportedGateways)) {
            throw new RuntimeException(
                "Unsupported gateway: {$gatewayName}. Supported gateways: " . implode(', ', $supportedGateways)
            );
        }
    }
}
