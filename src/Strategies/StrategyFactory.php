<?php

declare(strict_types=1);

namespace JaapTech\NepaliPayment\Strategies;

use Illuminate\Contracts\Config\Repository;
use JaapTech\NepaliPayment\Enums\NepaliPaymentGateway;
use RuntimeException;

/**
 * Factory for creating payment interceptor strategies.
 * Uses static methods for simple, stateless strategy creation.
 */
class StrategyFactory
{
    /**
     * Create a strategy instance for the given gateway.
     *
     * @param string|NepaliPaymentGateway $gateway Gateway name or enum
     * @param Repository $config Configuration repository
     * @return PaymentInterceptorStrategy
     *
     * @throws RuntimeException If gateway is not supported
     */
    public static function make(string|NepaliPaymentGateway $gateway, Repository $config): PaymentInterceptorStrategy
    {
        $gatewayName = $gateway instanceof NepaliPaymentGateway ? $gateway->value : $gateway;

        return match ($gatewayName) {
            NepaliPaymentGateway::KHALTI->value => new KhaltiStrategy($config),
            NepaliPaymentGateway::ESEWA->value => new EsewaStrategy($config),
            NepaliPaymentGateway::CONNECTIPS->value => new ConnectIpsStrategy($config),
            default => throw new RuntimeException("Unsupported gateway for strategy: {$gatewayName}"),
        };
    }
}
