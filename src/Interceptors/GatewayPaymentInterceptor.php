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

class GatewayPaymentInterceptor
{
    private bool $isDatabseEnabled;

    public function __construct(
        private readonly object $gateway,
        private readonly PaymentService $paymentService,
        private readonly string $gatewayName,
        protected Repository $config
    ) {
        $this->isDatabseEnabled = (bool) $config->get('nepali-payment.database.enabled', false);
    }

    /**
     * Intercept payment method call to auto-log to database.
     *
     * @throws DatabaseException
     */
    public function payment(array $data)
    {
        try {
            // Call actual gateway payment method
            if ($this->gatewayName === 'KHALTI') {
                $response = $this->gateway->payment(array_merge(
                    [
                        'success_url' => $this->config->get('nepali-payment.gateways.khalti.success_url'),
                        'website_url' => $this->config->get('nepali-payment.gateways.khalti.website_url'),
                    ],
                    $data,
                ));
            } else if ($this->gatewayName === 'ESEWA') {
                $response = $this->gateway->payment(array_merge(
                    [
                        'success_url' => $this->config->get('nepali-payment.gateways.esewa.success_url'),
                        'failure_url' => $this->config->get('nepali-payment.gateways.esewa.failure_url'),
                    ],
                    $data,
                ));
            } else if ($this->gatewayName === 'CONNECTIPS') {
                $response = $this->gateway->payment(array_merge(
                    [
                        'return_url' => $this->config->get('nepali-payment.gateways.connectips.return_url'),
                    ],
                    $data,
                ));
            } else {
                $response = $this->gateway->payment($data);
            }

            // Create payment record
            if ($this->isDatabseEnabled) {
                try {
                    $payment = $this->paymentService->createPayment(
                        gateway: $this->gatewayName,
                        amount: $data['total_amount'] ?? $data['amount'] ?? 0,
                        gatewayPayloadData: $data,
                        gatewayResponseData: $response->toArray(),
                    );
                }  catch (\Exception $e) {
                    throw DatabaseException::createFailed($this->gatewayName, $e->getMessage());
                }
                // Dispatch payment initiated event
                event(new PaymentInitiatedEvent($payment));
            }

            return $response;
        } catch (\Exception $e) {
//            throw DatabaseException::createFailed($this->gatewayName, $e->getMessage());
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
            // Try to find payment by gateway transaction ID
            if ($this->gatewayName === 'KHALTI') {
                $merchantReferenceId = $data['pidx'];

            } else if ($this->gatewayName === 'ESEWA') {
                $merchantReferenceId = $data['transaction_uuid'];

            } else if ($this->gatewayName === 'CONNECTIPS') {
                $merchantReferenceId = $data['transaction_uuid'] ?? $data['txn_id'];
            }

            $payment = $this->paymentService->findByReference($merchantReferenceId ?? '');

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
