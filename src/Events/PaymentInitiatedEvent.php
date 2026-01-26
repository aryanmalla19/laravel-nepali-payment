<?php

namespace JaapTech\NepaliPayment\Events;

use JaapTech\NepaliPayment\Models\PaymentTransaction;

class PaymentInitiatedEvent
{
    public function __construct(
        public readonly PaymentTransaction $payment
    ) {}
}
