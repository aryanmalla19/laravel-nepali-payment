<?php

declare(strict_types=1);

namespace JaapTech\NepaliPayment\Interceptors;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Support\Facades\Log;
use JaapTech\NepaliPayment\Events\PaymentFailedEvent;
use JaapTech\NepaliPayment\Events\PaymentInitiatedEvent;
use JaapTech\NepaliPayment\Events\PaymentProcessingEvent;
use JaapTech\NepaliPayment\Exceptions\DatabaseException;
use JaapTech\NepaliPayment\Services\PaymentService;
use JaapTech\NepaliPayment\Strategies\PaymentInterceptorStrategy;

class GatewayPaymentInterceptor
{
    private bool $isDatabaseEnabled;

    public function __construct(
        private readonly object $gateway,
        private readonly PaymentService $paymentService,
        private readonly PaymentInterceptorStrategy $strategy,
        protected Repository $config
    ) {
        $this->isDatabaseEnabled = (bool) $config->get('nepali-payment.database.enabled', false);
    }

    /**
     * Intercept payment method call to auto-log to database.
     *
     * @throws DatabaseException
     */
    public function payment(array $data)
    {
        try {
            // Build gateway-specific payment data using strategy
            $paymentData = $this->strategy->buildPaymentData($data);

            // Call actual gateway payment method
            $response = $this->gateway->payment($paymentData);

            // Create payment record
            if ($this->isDatabaseEnabled) {
                try {
                    $payment = $this->paymentService->createPayment(
                        gateway: $this->strategy::class,
                        amount: $data['total_amount'] ?? $data['amount'] ?? 0,
                        gatewayPayloadData: $data,
                        gatewayResponseData: $response->toArray(),
                    );
                } catch (\Exception $e) {
                    throw DatabaseException::createFailed($this->strategy::class, $e->getMessage());
                }
                // Dispatch payment initiated event
                event(new PaymentInitiatedEvent($payment));
            }

            return $response;
        } catch (\Exception $e) {
            Log::error("Payment interception failed: {$e->getMessage()}", [
                'data' => $data,
            ]);
            throw $e;
        }
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
            // Extract merchant reference ID using strategy
            $merchantReferenceId = $this->strategy->extractReferenceId($data);

            if ($merchantReferenceId === null) {
                Log::warning('Could not extract merchant reference ID from verification data', [
                    'data' => $data,
                ]);

                return $response;
            }

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
            Log::error("Failed to record payment verification: {$e->getMessage()}", [
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
