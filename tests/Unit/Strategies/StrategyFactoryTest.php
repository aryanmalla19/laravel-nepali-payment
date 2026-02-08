<?php

declare(strict_types=1);

namespace JaapTech\NepaliPayment\Tests\Unit\Strategies;

use Illuminate\Config\Repository;
use JaapTech\NepaliPayment\Enums\NepaliPaymentGateway;
use JaapTech\NepaliPayment\Strategies\ConnectIpsStrategy;
use JaapTech\NepaliPayment\Strategies\EsewaStrategy;
use JaapTech\NepaliPayment\Strategies\KhaltiStrategy;
use JaapTech\NepaliPayment\Strategies\StrategyFactory;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class StrategyFactoryTest extends TestCase
{
    private Repository $config;

    protected function setUp(): void
    {
        parent::setUp();

        $this->config = new Repository([
            'nepali-payment' => [
                'khalti' => [
                    'success_url' => 'https://example.com/success',
                    'website_url' => 'https://example.com',
                ],
                'esewa' => [
                    'success_url' => 'https://example.com/success',
                    'failure_url' => 'https://example.com/failure',
                ],
                'connectips' => [
                    'return_url' => 'https://example.com/return',
                ],
            ],
        ]);
    }

    public function test_make_returns_khalti_strategy_for_khalti_gateway()
    {
        $strategy = StrategyFactory::make('khalti', $this->config);

        $this->assertInstanceOf(KhaltiStrategy::class, $strategy);
        $this->assertInstanceOf(\JaapTech\NepaliPayment\Strategies\PaymentInterceptorStrategy::class, $strategy);
    }

    public function test_make_returns_khalti_strategy_for_khalti_enum()
    {
        $strategy = StrategyFactory::make(NepaliPaymentGateway::KHALTI, $this->config);

        $this->assertInstanceOf(KhaltiStrategy::class, $strategy);
    }

    public function test_make_returns_esewa_strategy_for_esewa_gateway()
    {
        $strategy = StrategyFactory::make('esewa', $this->config);

        $this->assertInstanceOf(EsewaStrategy::class, $strategy);
    }

    public function test_make_returns_esewa_strategy_for_esewa_enum()
    {
        $strategy = StrategyFactory::make(NepaliPaymentGateway::ESEWA, $this->config);

        $this->assertInstanceOf(EsewaStrategy::class, $strategy);
    }

    public function test_make_returns_connectips_strategy_for_connectips_gateway()
    {
        $strategy = StrategyFactory::make('connectips', $this->config);

        $this->assertInstanceOf(ConnectIpsStrategy::class, $strategy);
    }

    public function test_make_returns_connectips_strategy_for_connectips_enum()
    {
        $strategy = StrategyFactory::make(NepaliPaymentGateway::CONNECTIPS, $this->config);

        $this->assertInstanceOf(ConnectIpsStrategy::class, $strategy);
    }

    public function test_make_throws_exception_for_unsupported_gateway()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unsupported gateway for strategy');

        StrategyFactory::make('INVALID_GATEWAY', $this->config);
    }

    public function test_make_is_static_method()
    {
        $this->assertTrue(method_exists(StrategyFactory::class, 'make'));

        // Should be callable statically without instantiation
        $strategy = StrategyFactory::make('khalti', $this->config);
        $this->assertInstanceOf(KhaltiStrategy::class, $strategy);
    }

    public function test_created_strategies_have_config_injected()
    {
        $strategy = StrategyFactory::make('khalti', $this->config);

        // Verify strategy works with injected config
        $data = ['amount' => 100];
        $result = $strategy->buildPaymentData($data);

        $this->assertEquals('https://example.com/success', $result['success_url']);
    }

    public function test_created_strategies_are_independent_instances()
    {
        $strategy1 = StrategyFactory::make('khalti', $this->config);
        $strategy2 = StrategyFactory::make('khalti', $this->config);

        $this->assertNotSame($strategy1, $strategy2);
    }
}
