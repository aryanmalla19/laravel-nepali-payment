<?php

namespace JaapTech\NepaliPayment\Events;

use JaapTech\NepaliPayment\Models\PaymentTransaction;

class PaymentCompletedEvent
{
    public function __construct(
        public readonly PaymentTransaction $payment
    ) {}
}
