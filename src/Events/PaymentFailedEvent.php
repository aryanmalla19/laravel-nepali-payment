<?php

namespace JaapTech\NepaliPayment\Events;

use JaapTech\NepaliPayment\Models\Payment;

class PaymentFailedEvent
{
    public function __construct(
        public readonly Payment $payment,
        public readonly ?string $reason = null
    ) {}
}
