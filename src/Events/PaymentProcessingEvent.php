<?php

namespace JaapTech\NepaliPayment\Events;

use JaapTech\NepaliPayment\Models\Payment;

class PaymentProcessingEvent
{
    public function __construct(
        public readonly Payment $payment
    ) {}
}
