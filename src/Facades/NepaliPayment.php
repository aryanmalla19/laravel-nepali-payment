<?php

declare(strict_types=1);

namespace JaapTech\NepaliPayment\Facades;

use Illuminate\Support\Facades\Facade;
use JaapTech\NepaliPayment\Services\PaymentManager;

/**
 * @method static \Kbk\NepaliPaymentGateway\Epay\Esewa esewa()
 * @method static \Kbk\NepaliPaymentGateway\Epay\Khalti khalti()
 * @method static \Kbk\NepaliPaymentGateway\Epay\ConnectIps connectIps()
 */
class NepaliPayment extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return PaymentManager::class;
    }
}