<?php

declare(strict_types=1);

namespace JaapTech\NepaliPayment\Interceptors;

use Illuminate\Contracts\Config\Repository;
use JaapTech\NepaliPayment\Events\PaymentFailedEvent;
use JaapTech\NepaliPayment\Events\PaymentInitiatedEvent;
use JaapTech\NepaliPayment\Events\PaymentProcessingEvent;
use JaapTech\NepaliPayment\Exceptions\DatabaseException;
use JaapTech\NepaliPayment\Services\PaymentService;

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
     *
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
                gatewayPayloadData: $data,
                gatewayResponseData: $response->toArray(),
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
            $merchantReferenceId = $data['pidx'] ?? $data['transaction_uuid'] ?? $data['txn_id'] ?? '';

            $payment = $this->paymentService->findByReference($merchantReferenceId);

            if ($payment) {
                $isSuccess = $response->isSuccess();

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
     * Dynamically forward any other method calls to the gateway.
     */
    public function __call(string $method, array $arguments)
    {
        return call_user_func_array([$this->gateway, $method], $arguments);
    }
}
