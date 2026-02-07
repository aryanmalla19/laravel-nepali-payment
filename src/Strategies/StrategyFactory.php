<?php

declare(strict_types=1);

namespace JaapTech\NepaliPayment\Strategies;

use Illuminate\Contracts\Config\Repository;
use JaapTech\NepaliPayment\Enums\NepaliPaymentGateway;
use RuntimeException;

/**
 * Factory for creating payment interceptor strategies.
 */
class StrategyFactory
{
    public function __construct(private readonly Repository $config) {}

    /**
     * Create a strategy instance for the given gateway.
     *
     * @param string|NepaliPaymentGateway $gateway Gateway name or enum
     * @return PaymentInterceptorStrategy
     *
     * @throws RuntimeException If gateway is not supported
     */
    public function make(string|NepaliPaymentGateway $gateway): PaymentInterceptorStrategy
    {
        $gatewayName = $gateway instanceof NepaliPaymentGateway ? $gateway->value : $gateway;

        return match ($gatewayName) {
            NepaliPaymentGateway::KHALTI->value => new KhaltiStrategy($this->config),
            NepaliPaymentGateway::ESEWA->value => new EsewaStrategy($this->config),
            NepaliPaymentGateway::CONNECTIPS->value => new ConnectIpsStrategy($this->config),
            default => throw new RuntimeException("Unsupported gateway for strategy: {$gatewayName}"),
        };
    }
}
