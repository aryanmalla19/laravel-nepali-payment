<?php

declare(strict_types=1);

namespace JaapTech\NepaliPayment\Tests\Unit\Enums;

use JaapTech\NepaliPayment\Enums\NepaliPaymentGateway;
use PHPUnit\Framework\TestCase;

class NepaliPaymentGatewayTest extends TestCase
{
    public function test_nepali_payment_gateway_has_all_expected_cases()
    {
        $cases = NepaliPaymentGateway::cases();
        $values = array_map(fn($case) => $case->value, $cases);

        $this->assertContains('esewa', $values);
        $this->assertContains('khalti', $values);
        $this->assertContains('connectips', $values);
    }

    public function test_esewa_gateway_has_correct_value()
    {
        $this->assertEquals('esewa', NepaliPaymentGateway::ESEWA->value);
    }

    public function test_khalti_gateway_has_correct_value()
    {
        $this->assertEquals('khalti', NepaliPaymentGateway::KHALTI->value);
    }

    public function test_connectips_gateway_has_correct_value()
    {
        $this->assertEquals('connectips', NepaliPaymentGateway::CONNECTIPS->value);
    }

    public function test_can_create_gateway_from_string()
    {
        $gateway = NepaliPaymentGateway::from('khalti');
        $this->assertEquals(NepaliPaymentGateway::KHALTI, $gateway);
    }

    public function test_creating_from_invalid_string_throws_exception()
    {
        $this->expectException(\ValueError::class);
        NepaliPaymentGateway::from('INVALID_GATEWAY');
    }

    public function test_try_from_returns_null_for_invalid_string()
    {
        $gateway = NepaliPaymentGateway::tryFrom('INVALID_GATEWAY');
        $this->assertNull($gateway);
    }

    public function test_try_from_returns_enum_for_valid_string()
    {
        $gateway = NepaliPaymentGateway::tryFrom('esewa');
        $this->assertEquals(NepaliPaymentGateway::ESEWA, $gateway);
    }
}
