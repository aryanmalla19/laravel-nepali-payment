<?php

declare(strict_types=1);

namespace JaapTech\NepaliPayment\Facades;

use Illuminate\Support\Facades\Facade;
use JaapTech\NepaliPayment\Services\PaymentManager;

/**
 * @method static \Kbk\NepaliPaymentGateway\Epay\Esewa esewa()
 * @method static \Kbk\NepaliPaymentGateway\Epay\Khalti khalti()
 * @method static \Kbk\NepaliPaymentGateway\Epay\ConnectIps connectips()
 * @method static \JaapTech\NepaliPayment\Models\Payment createPayment(string $gateway, float $amount, array $paymentData = [], ?string $payableType = null, int|string|null $payableId = null, array $metadata = [])
 * @method static void recordPaymentVerification(\JaapTech\NepaliPayment\Models\Payment $payment, array $verificationData, bool $isSuccess = true)
 * @method static void completePayment(\JaapTech\NepaliPayment\Models\Payment $payment, ?string $gatewayTransactionId = null, array $responseData = [])
 * @method static void failPayment(\JaapTech\NepaliPayment\Models\Payment $payment, ?string $reason = null)
 * @method static \JaapTech\NepaliPayment\Models\PaymentRefund createRefund(\JaapTech\NepaliPayment\Models\Payment $payment, float $refundAmount, \JaapTech\NepaliPayment\Enums\RefundReason|string $reason = 'user_request', ?string $notes = null, int|string|null $requestedBy = null)
 * @method static void processRefund(\JaapTech\NepaliPayment\Models\PaymentRefund $refund, array $responseData = [], bool $isSuccess = true)
 * @method static \JaapTech\NepaliPayment\Models\Payment|null findPaymentByReference(string $referenceId)
 * @method static \JaapTech\NepaliPayment\Models\Payment|null findPaymentByGatewayId(string $gatewayTransactionId)
 * @method static \Illuminate\Database\Eloquent\Builder getPaymentsByStatus(\JaapTech\NepaliPayment\Enums\PaymentStatus|string $status)
 * @method static \Illuminate\Database\Eloquent\Builder getPaymentsByGateway(string $gateway)
 */
class NepaliPayment extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return PaymentManager::class;
    }
}