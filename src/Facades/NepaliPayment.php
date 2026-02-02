<?php

declare(strict_types=1);

namespace JaapTech\NepaliPayment\Facades;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Facade;
use JaapTech\NepaliPayment\Enums\PaymentStatus;
use JaapTech\NepaliPayment\Enums\RefundReason;
use JaapTech\NepaliPayment\Models\PaymentRefund;
use JaapTech\NepaliPayment\Models\PaymentTransaction;
use JaapTech\NepaliPayment\Services\PaymentManager;
use Kbk\NepaliPaymentGateway\Epay\ConnectIps;
use Kbk\NepaliPaymentGateway\Epay\Esewa;
use Kbk\NepaliPaymentGateway\Epay\Khalti;

/**
 * @method static Esewa esewa()
 * @method static Khalti khalti()
 * @method static ConnectIps connectips()
 * @method static PaymentTransaction createPayment(string $gateway, float $amount, array $paymentData = [], ?string $payableType = null, int|string|null $payableId = null, array $metadata = [])
 * @method static void recordPaymentVerification(PaymentTransaction $payment, array $verificationData, bool $isSuccess = true)
 * @method static void completePayment(PaymentTransaction $payment, ?string $gatewayTransactionId = null, array $responseData = [])
 * @method static void failPayment(PaymentTransaction $payment, ?string $reason = null)
 * @method static PaymentRefund createRefund(PaymentTransaction $payment, float $refundAmount, RefundReason|string $reason = 'user_request', ?string $notes = null, int|string|null $requestedBy = null)
 * @method static void processRefund(PaymentRefund $refund, array $responseData = [], bool $isSuccess = true)
 * @method static PaymentTransaction|null findPaymentByReference(string $referenceId)
 * @method static PaymentTransaction|null findPaymentByGatewayId(string $gatewayTransactionId)
 * @method static Builder getPaymentsByStatus(PaymentStatus|string $status)
 * @method static Builder getPaymentsByGateway(string $gateway)
 * @method static Builder getPaymentsForPayable(string $payableType, int|string $payableId)
 */
class NepaliPayment extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return PaymentManager::class;
    }
}
