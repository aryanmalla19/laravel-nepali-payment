<?php

declare(strict_types=1);

namespace JaapTech\NepaliPayment\Services;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\Eloquent\Builder;
use JaapTech\NepaliPayment\Enums\NepaliPaymentGateway;
use JaapTech\NepaliPayment\Enums\PaymentStatus;
use JaapTech\NepaliPayment\Exceptions\DatabaseException;
use JaapTech\NepaliPayment\Factories\GatewayFactory;
use JaapTech\NepaliPayment\Interceptors\GatewayPaymentInterceptor;
use JaapTech\NepaliPayment\Models\PaymentTransaction;
use Kbk\NepaliPaymentGateway\Exceptions\InvalidPayloadException;
use RuntimeException;

/**
 * PaymentManager - Orchestrator for payment operations
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
        $this->isDatabaseEnabled = (bool) $config->get('nepali-payment.database.enabled', false);
    }

    // ============= Gateway Access with Auto-Logging =============

    /**
     * Get a gateway instance with auto-logging interceptor if database is enabled.
     *
     * @param  string|NepaliPaymentGateway  $gateway  Gateway name or enum
     * @return object Gateway instance or intercepted gateway
     *
     * @throws RuntimeException|InvalidPayloadException If gateway is not supported
     */
    public function gateway(string|NepaliPaymentGateway $gateway): object
    {
        // Validate and get gateway instance from factory
        $gatewayInstance = $this->gatewayFactory->make($gateway);

        $gatewayName = $gateway instanceof NepaliPaymentGateway ? $gateway->value : $gateway;

        return new GatewayPaymentInterceptor($gatewayInstance, $this->paymentService, $gatewayName, $this->config);
    }

    /**
     * Get eSewa gateway instance with auto-logging interceptor.
     *
     * @throws InvalidPayloadException
     */
    public function esewa(): object
    {
        return $this->gateway(NepaliPaymentGateway::ESEWA);
    }

    /**
     * Get Khalti gateway instance with auto-logging interceptor.
     *
     * @throws InvalidPayloadException
     */
    public function khalti(): object
    {
        return $this->gateway(NepaliPaymentGateway::KHALTI);
    }

    /**
     * Get ConnectIps gateway instance with auto-logging interceptor.
     *
     * @throws InvalidPayloadException
     */
    public function connectips(): object
    {
        return $this->gateway(NepaliPaymentGateway::CONNECTIPS);
    }

    /**
     * Mark a payment as completed.
     *
     * @throws DatabaseException
     */
    public function completePayment(PaymentTransaction $payment): void
    {
        $this->paymentService->completePayment($payment);
    }

    /**
     * Find a payment by reference ID.
     *
     * @throws DatabaseException
     */
    public function findPaymentByReference(string $referenceId): ?PaymentTransaction
    {
        return $this->paymentService->findByReference($referenceId);
    }

    /**
     * Get all payments by status.
     *
     * @throws DatabaseException
     */
    public function getPaymentsByStatus(PaymentStatus|string $status): Builder
    {
        return $this->queryService->getByStatus($status);
    }

    /**
     * Get all payments by gateway.
     *
     * @throws RuntimeException|DatabaseException If gateway is not supported
     */
    public function getPaymentsByGateway(string|NepaliPaymentGateway $gateway): Builder
    {
        // Validate gateway
        $gatewayName = $gateway instanceof NepaliPaymentGateway ? $gateway->value : $gateway;

        $this->validateGateway($gatewayName);

        return $this->queryService->getByGateway($gatewayName);
    }

    /**
     * Get all payments for a specific payable model.
     *
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

        if (! in_array($gatewayName, $supportedGateways)) {
            throw new RuntimeException(
                "Unsupported gateway: {$gatewayName}. Supported gateways: ".implode(', ', $supportedGateways)
            );
        }
    }
}
