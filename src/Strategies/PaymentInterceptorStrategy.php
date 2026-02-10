<?php

declare(strict_types=1);

namespace JaapTech\NepaliPayment\Strategies;

/**
 * Interface for payment interceptor strategies.
 * Each gateway implements this to handle gateway-specific logic.
 */
interface PaymentInterceptorStrategy
{
    /**
     * Build payment data with gateway-specific URL parameters.
     *
     * @param  array<string, mixed>  $data  Base payment data
     * @return array<string, mixed> Payment data with gateway-specific params
     */
    public function buildPaymentData(array $data): array;

    /**
     * Extract merchant reference ID from verification data.
     *
     * @param  array<string, mixed>  $data  Verification data from gateway callback
     * @return string|null Merchant reference ID or null if not found
     */
    public function extractReferenceId(array $data): ?string;
}
