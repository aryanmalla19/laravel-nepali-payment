<?php

declare(strict_types=1);

namespace Jaap\NepaliPayment\Facades;

use Illuminate\Support\Facades\Facade;
use Jaap\NepaliPayment\Services\PaymentManager;

class NepaliPayment extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return PaymentManager::class;
    }
}