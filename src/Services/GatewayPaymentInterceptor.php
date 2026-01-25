<?php

declare(strict_types=1);

namespace JaapTech\NepaliPayment\Services;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Support\Str;
use JaapTech\NepaliPayment\Events\PaymentCompletedEvent;
use JaapTech\NepaliPayment\Events\PaymentFailedEvent;
use JaapTech\NepaliPayment\Events\PaymentInitiatedEvent;
use JaapTech\NepaliPayment\Events\PaymentProcessingEvent;
use JaapTech\NepaliPayment\Exceptions\DatabaseException;
use JaapTech\NepaliPayment\Models\Payment;

class GatewayPaymentInterceptor
{
    private bool $isDatabaseEnabled;

    public function __construct(
        private object $gateway,
        private PaymentService $paymentService,
        private string $gatewayName,
        protected Repository $config
    ) {
        $this->isDatabaseEnabled = (bool) $config->get('nepali-payment.database.enabled', false);
    }

    /**
     * Intercept payment method call to auto-log to database.
     */
    public function payment(array $data)
    {
        $payment = null;

        // Step 1: Create payment record if database enabled
        if ($this->isDatabaseEnabled) {
            try {
                $referenceId = $data['transaction_uuid'] ?? $data['reference_id'] ?? Str::uuid()->toString();

                $payment = $this->paymentService->createPayment(
                    gateway: $this->gatewayName,
                    amount: $data['amount'] ?? 0,
                    paymentData: $data,
                    metadata: $data
                );
            } catch (\Exception $e) {
                // Log error but don't stop the process
                \Log::error("Failed to create payment record: {$e->getMessage()}", [
                    'gateway' => $this->gatewayName,
                    'data' => $data,
                ]);
                
                throw DatabaseException::createFailed($this->gatewayName, $e->getMessage());
            }

            // Dispatch payment initiated event
            event(new PaymentInitiatedEvent($payment));
        }

        // Step 2: Call actual gateway payment method
        $response = $this->gateway->payment($data);

        // Step 3: Store transaction ID and full response if payment was created
        if ($this->isDatabaseEnabled && $payment) {
            try {
                // Extract transaction ID from response if available
                $transactionId = $this->extractTransactionId($response);

                $payment->update([
                    'gateway_transaction_id' => $transactionId,
                    'gateway_response' => $this->responseToArray($response),
                ]);
            } catch (\Exception $e) {
                \Log::error("Failed to update payment record: {$e->getMessage()}", [
                    'payment_id' => $payment->id,
                ]);
            }
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

        // Step 2: Update payment record if database enabled
        if ($this->isDatabaseEnabled) {
            try {
                // Try to find payment by gateway transaction ID
                $payment = $this->paymentService->findByGatewayId(
                    $data['transaction_uuid'] ?? $data['transaction_id'] ?? $data['txn_id'] ?? ''
                );

                if ($payment) {
                    $isSuccess = $this->isResponseSuccess($response);

                    $this->paymentService->recordPaymentVerification(
                        $payment,
                        $this->responseToArray($response),
                        $isSuccess
                    );

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
        }

        // Step 3: Return response to user
        return $response;
    }

    /**
     * Extract transaction ID from gateway response.
     */
    private function extractTransactionId(object $response): ?string
    {
        // Try common method names used by gateways
        if (method_exists($response, 'getTransactionId')) {
            return $response->getTransactionId();
        }

        if (method_exists($response, 'transactionId')) {
            return $response->transactionId();
        }

        if (method_exists($response, 'getTxnId')) {
            return $response->getTxnId();
        }

        // Try property access
        if (isset($response->transaction_id)) {
            return $response->transaction_id;
        }

        if (isset($response->txn_id)) {
            return $response->txn_id;
        }

        return null;
    }

    /**
     * Convert gateway response to array format for storage.
     */
    private function responseToArray(object $response): array
    {
        // If response has toArray method, use it
        if (method_exists($response, 'toArray')) {
            return $response->toArray();
        }

        // If response has toJson method, decode it
        if (method_exists($response, 'toJson')) {
            return json_decode($response->toJson(), true) ?? [];
        }

        // Fallback: convert object to array
        return json_decode(json_encode($response), true) ?? [];
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
