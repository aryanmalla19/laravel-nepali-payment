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
        // Step 1: Create payment record
        try {
            $payment = $this->paymentService->createPayment(
                gateway: $this->gatewayName,
                amount: $data['amount'] ?? 0,
                paymentData: $data,
            );
        } catch (\Exception $e) {
            \Log::error("Failed to create payment record: {$e->getMessage()}", [
                'gateway' => $this->gatewayName,
                'data' => $data,
            ]);

            throw DatabaseException::createFailed($this->gatewayName, $e->getMessage());
        }

        // Dispatch payment initiated event
        event(new PaymentInitiatedEvent($payment));

        // Step 2: Call actual gateway payment method
        $response = $this->gateway->payment($data);

        // Step 3: Store transaction ID and full response
        try {
            $transactionId = $this->extractTransactionIdFromArray($response->toArray());

            $payment->update([
                'transaction_id' => $transactionId,
                'gateway_response' => $response->toArray(),
            ]);
        } catch (\Exception $e) {
            \Log::error("Failed to update payment record: {$e->getMessage()}", [
                'payment_id' => $payment->id,
            ]);
        }

        // Step 4: Return response to user
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
            $transactionId = $data['transaction_uuid'] ?? $data['transaction_id'] ?? $data['txn_id'] ?? '';

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
    private function extractTransactionIdFromArray(array $response): ?string
    {
        // Try property access
        if (isset($response['transaction_id'])) return $response['transaction_id'];

        if (isset($response['transaction_uuid'])) return $response['transaction_uuid'];

        if (isset($response['txn_id'])) return $response['txn_id'];

        if (isset($response['pidx'])) return $response['pidx'];

        return null;
    }

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
