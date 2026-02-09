<?php

declare(strict_types=1);

namespace JaapTech\NepaliPayment\Strategies;

use Illuminate\Contracts\Config\Repository;

/**
 * Khalti payment interceptor strategy.
 */
class KhaltiStrategy implements PaymentInterceptorStrategy
{
    public function __construct(private readonly Repository $config) {}

    public function buildPaymentData(array $data): array
    {
        return array_merge([
            'return_url' => $this->config->get('nepali-payment.khalti.success_url'),
            'website_url' => $this->config->get('nepali-payment.khalti.website_url'),
        ], $data);
    }

    public function extractReferenceId(array $data): ?string
    {
        return $data['pidx'] ?? null;
    }
}
