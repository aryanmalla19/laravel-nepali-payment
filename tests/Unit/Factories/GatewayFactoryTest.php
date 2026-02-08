<?php

declare(strict_types=1);

namespace JaapTech\NepaliPayment\Tests\Unit\Factories;

use JaapTech\NepaliPayment\Enums\NepaliPaymentGateway;
use JaapTech\NepaliPayment\Factories\GatewayFactory;
use JaapTech\NepaliPayment\Tests\TestCase;
use Kbk\NepaliPaymentGateway\Contracts\BasePaymentGateway;
use Kbk\NepaliPaymentGateway\Epay\ConnectIps;
use Kbk\NepaliPaymentGateway\Epay\Esewa;
use Kbk\NepaliPaymentGateway\Epay\Khalti;
use RuntimeException;

class GatewayFactoryTest extends TestCase
{
    private GatewayFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->factory = app(GatewayFactory::class);
    }

    public function test_make_returns_khalti_instance()
    {
        $gateway = $this->factory->make('khalti');

        $this->assertInstanceOf(Khalti::class, $gateway);
        $this->assertInstanceOf(BasePaymentGateway::class, $gateway);
    }

    public function test_make_returns_khalti_instance_for_enum()
    {
        $gateway = $this->factory->make(NepaliPaymentGateway::KHALTI);

        $this->assertInstanceOf(Khalti::class, $gateway);
    }

    public function test_make_returns_esewa_instance()
    {
        $gateway = $this->factory->make('esewa');

        $this->assertInstanceOf(Esewa::class, $gateway);
        $this->assertInstanceOf(BasePaymentGateway::class, $gateway);
    }

    public function test_make_returns_esewa_instance_for_enum()
    {
        $gateway = $this->factory->make(NepaliPaymentGateway::ESEWA);

        $this->assertInstanceOf(Esewa::class, $gateway);
    }

    public function test_make_returns_connectips_instance()
    {
        $gateway = $this->factory->make('connectips');

        $this->assertInstanceOf(ConnectIps::class, $gateway);
        $this->assertInstanceOf(BasePaymentGateway::class, $gateway);
    }

    public function test_make_returns_connectips_instance_for_enum()
    {
        $gateway = $this->factory->make(NepaliPaymentGateway::CONNECTIPS);

        $this->assertInstanceOf(ConnectIps::class, $gateway);
    }

    public function test_make_throws_exception_for_unsupported_gateway()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unsupported gateway');

        $this->factory->make('INVALID_GATEWAY');
    }

    public function test_make_caches_gateway_instance()
    {
        $gateway1 = $this->factory->make('khalti');
        $gateway2 = $this->factory->make('khalti');

        $this->assertSame($gateway1, $gateway2);
    }

    public function test_is_cached_returns_false_for_new_gateway()
    {
        $this->assertFalse($this->factory->isCached('khalti'));
    }

    public function test_is_cached_returns_true_after_make()
    {
        $this->factory->make('khalti');

        $this->assertTrue($this->factory->isCached('khalti'));
    }

    public function test_is_cached_returns_true_after_make_with_enum()
    {
        $this->factory->make(NepaliPaymentGateway::KHALTI);

        $this->assertTrue($this->factory->isCached('khalti'));
    }

    public function test_forget_removes_cached_gateway()
    {
        $this->factory->make('khalti');
        $this->assertTrue($this->factory->isCached('khalti'));

        $this->factory->forget('khalti');

        $this->assertFalse($this->factory->isCached('khalti'));
    }

    public function test_forget_works_with_enum()
    {
        $this->factory->make(NepaliPaymentGateway::KHALTI);
        $this->factory->forget(NepaliPaymentGateway::KHALTI);

        $this->assertFalse($this->factory->isCached('KHALTI'));
    }

    public function test_flush_removes_all_cached_gateways()
    {
        $this->factory->make('khalti');
        $this->factory->make('esewa');
        $this->factory->make('connectips');

        $this->assertTrue($this->factory->isCached('khalti'));
        $this->assertTrue($this->factory->isCached('esewa'));
        $this->assertTrue($this->factory->isCached('connectips'));

        $this->factory->flush();

        $this->assertFalse($this->factory->isCached('khalti'));
        $this->assertFalse($this->factory->isCached('esewa'));
        $this->assertFalse($this->factory->isCached('connectips'));
    }

    public function test_make_creates_new_instance_after_forget()
    {
        $gateway1 = $this->factory->make('khalti');
        $this->factory->forget('khalti');
        $gateway2 = $this->factory->make('khalti');

        $this->assertNotSame($gateway1, $gateway2);
    }

    public function test_throws_exception_for_missing_esewa_config()
    {
        config(['nepali-payment.esewa.product_code' => null]);
        config(['nepali-payment.esewa.secret_key' => null]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Missing config');

        $this->factory->make('esewa');
    }

    public function test_throws_exception_for_missing_khalti_config()
    {
        config(['nepali-payment.khalti.secret_key' => null]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Missing config');

        $this->factory->make('khalti');
    }

    public function test_singleton_behavior_across_container()
    {
        $factory1 = app(GatewayFactory::class);
        $factory2 = app(GatewayFactory::class);

        $this->assertSame($factory1, $factory2);
    }
}
