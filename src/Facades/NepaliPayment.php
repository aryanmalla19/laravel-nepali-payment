<?php

declare(strict_types=1);

namespace JaapTech\NepaliPayment\Facades;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Facade;
use JaapTech\NepaliPayment\Enums\PaymentStatus;
use JaapTech\NepaliPayment\Models\PaymentTransaction;
use JaapTech\NepaliPayment\Services\PaymentManager;
use Kbk\NepaliPaymentGateway\Epay\ConnectIps;
use Kbk\NepaliPaymentGateway\Epay\Esewa;
use Kbk\NepaliPaymentGateway\Epay\Khalti;

/**
 * @method static Esewa esewa()
 * @method static Khalti khalti()
 * @method static ConnectIps connectips()
 * @method static void completePayment(PaymentTransaction $payment, ?string $gatewayTransactionId = null, array $responseData = [])
 * @method static void failPayment(PaymentTransaction $payment, ?string $reason = null)
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
