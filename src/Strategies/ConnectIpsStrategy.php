<?php

declare(strict_types=1);

namespace JaapTech\NepaliPayment\Strategies;

use Illuminate\Contracts\Config\Repository;

/**
 * ConnectIps payment interceptor strategy.
 */
class ConnectIpsStrategy implements PaymentInterceptorStrategy
{
    public function __construct(private readonly Repository $config) {}

    public function buildPaymentData(array $data): array
    {
        // Add any ConnectIps specific data transformations here if needed in future
        return $data;
    }

    public function extractReferenceId(array $data): ?string
    {
        return $data['transaction_uuid'] ?? $data['txn_id'] ?? null;
    }
}
