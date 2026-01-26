<?php

namespace JaapTech\NepaliPayment\Events;

use JaapTech\NepaliPayment\Models\PaymentTransaction;

class PaymentProcessingEvent
{
    public function __construct(
        public readonly PaymentTransaction $payment
    ) {}
}
