<?php

declare(strict_types=1);

namespace JaapTech\NepaliPayment\Strategies;

use Illuminate\Contracts\Config\Repository;

/**
 * eSewa payment interceptor strategy.
 */
class EsewaStrategy implements PaymentInterceptorStrategy
{
    public function __construct(private readonly Repository $config) {}

    public function buildPaymentData(array $data): array
    {
        return array_merge([
            'success_url' => $this->config->get('nepali-payment.esewa.success_url'),
            'failure_url' => $this->config->get('nepali-payment.esewa.failure_url'),
        ], $data);
    }

    public function extractReferenceId(array $data): ?string
    {
        return $data['transaction_uuid'] ?? null;
    }
}
