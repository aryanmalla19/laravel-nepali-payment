<?php

namespace JaapTech\NepaliPayment\Events;

use JaapTech\NepaliPayment\Models\PaymentRefund;

class PaymentRefundedEvent
{
    public function __construct(
        public readonly PaymentRefund $refund
    ) {}
}
