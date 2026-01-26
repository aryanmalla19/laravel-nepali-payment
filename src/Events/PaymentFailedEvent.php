<?php

namespace JaapTech\NepaliPayment\Events;

use JaapTech\NepaliPayment\Models\PaymentTransaction;

class PaymentFailedEvent
{
    public function __construct(
        public readonly PaymentTransaction $payment,
        public readonly ?string            $reason = null
    ) {}
}
