<?php

declare(strict_types=1);

namespace JaapTech\NepaliPayment\Services;

use Illuminate\Contracts\Config\Repository;
use JaapTech\NepaliPayment\Events\PaymentFailedEvent;
use JaapTech\NepaliPayment\Events\PaymentInitiatedEvent;
use JaapTech\NepaliPayment\Events\PaymentProcessingEvent;
use JaapTech\NepaliPayment\Exceptions\DatabaseException;

class GatewayPaymentInterceptor
{
    public function __construct(
        private readonly object $gateway,
        private readonly PaymentService $paymentService,
        private readonly string $gatewayName,
        protected Repository $config
    ) {}

    /**
     * Intercept payment method call to auto-log to database.
     * @throws DatabaseException
     */
    public function payment(array $data)
    {
        try {
            // Call actual gateway payment method
            $response = $this->gateway->payment($data);

            // Create payment record
            $payment = $this->paymentService->createPayment(
                gateway: $this->gatewayName,
                amount: $data['total_amount'] ?? $data['amount'] ?? 0,
                paymentData: array_merge($response->toArray(), $data),
            );

            // Dispatch payment initiated event
            event(new PaymentInitiatedEvent($payment));
        } catch (\Exception $e) {
            \Log::error("Failed to create payment record: {$e->getMessage()}", [
                'gateway' => $this->gatewayName,
                'data' => $data,
            ]);

            throw DatabaseException::createFailed($this->gatewayName, $e->getMessage());
        }

        return $response;
    }

    /**
     * Intercept verify method call to auto-log verification to database.
     */
    public function verify(array $data)
    {
        // Step 1: Call actual gateway verify method
        $response = $this->gateway->verify($data);

        // Step 2: Update payment record
        try {
            // Try to find payment by gateway transaction ID
            $transactionId = $data['pidx'] ?? $data['transaction_uuid'] ?? $data['transaction_id'] ?? $data['txn_id'] ?? '';

            $payment = $this->paymentService->findByTransactionId($transactionId);

            if ($payment) {
                $isSuccess = $this->isResponseSuccess($response);

                $this->paymentService->recordPaymentVerification($payment, $response->toArray(), $isSuccess);

                // Dispatch appropriate event based on result
                if ($isSuccess) {
                    event(new PaymentProcessingEvent($payment));
                } else {
                    event(new PaymentFailedEvent($payment, 'Verification failed'));
                }
            }
        } catch (\Exception $e) {
            \Log::error("Failed to record payment verification: {$e->getMessage()}", [
                'data' => $data,
            ]);
        }

        // Step 3: Return response to user
        return $response;
    }

    /**
     * Extract transaction ID from gateway response.
     */

    /**
     * Determine if response indicates success.
     */
    private function isResponseSuccess(object $response): bool
    {
        // Try common method names
        if (method_exists($response, 'isSuccess')) {
            return (bool) $response->isSuccess();
        }

        if (method_exists($response, 'getStatus')) {
            $status = $response->getStatus();

            return in_array(strtolower($status), ['success', 'completed', 'approved']);
        }

        // Try property access
        if (isset($response->status)) {
            return in_array(strtolower($response->status), ['success', 'completed', 'approved']);
        }

        return false;
    }

    /**
     * Dynamically forward any other method calls to the gateway.
     */
    public function __call(string $method, array $arguments)
    {
        return call_user_func_array([$this->gateway, $method], $arguments);
    }
}
