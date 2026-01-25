<?php

namespace JaapTech\NepaliPayment\Enums;

enum NepaliPaymentGateway: string
{
    case ESEWA = 'esewa';
    case KHALTI = 'khalti';
    case CONNECTIPS = 'connectips';


    /**
     * Get human-readable label for the gateways.
     */
    public function label(): string
    {
        return match ($this) {
            self::ESEWA => 'Esewa',
            self::KHALTI => 'Khalti',
            self::CONNECTIPS => 'Connect Ips',
        };
    }
}
