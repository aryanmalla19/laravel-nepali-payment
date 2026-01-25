<?php

namespace JaapTech\NepaliPayment\Events;

use JaapTech\NepaliPayment\Models\Payment;

class PaymentInitiatedEvent
{
    public function __construct(
        public readonly Payment $payment
    ) {}
}
