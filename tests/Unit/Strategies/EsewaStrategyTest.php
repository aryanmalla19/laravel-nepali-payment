<?php

declare(strict_types=1);

namespace JaapTech\NepaliPayment\Tests\Unit\Strategies;

use Illuminate\Config\Repository;
use JaapTech\NepaliPayment\Strategies\EsewaStrategy;
use PHPUnit\Framework\TestCase;

class EsewaStrategyTest extends TestCase
{
    private Repository $config;
    private EsewaStrategy $strategy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->config = new Repository([
            'nepali-payment' => [
                'esewa' => [
                    'success_url' => 'https://example.com/success',
                    'failure_url' => 'https://example.com/failure',
                ],
            ],
        ]);

        $this->strategy = new EsewaStrategy($this->config);
    }

    public function test_build_payment_data_merges_config_urls()
    {
        $data = [
            'total_amount' => 1000,
            'transaction_uuid' => 'txn-123',
        ];

        $result = $this->strategy->buildPaymentData($data);

        $this->assertEquals('https://example.com/success', $result['success_url']);
        $this->assertEquals('https://example.com/failure', $result['failure_url']);
        $this->assertEquals(1000, $result['total_amount']);
        $this->assertEquals('txn-123', $result['transaction_uuid']);
    }

    public function test_build_payment_data_preserves_original_data()
    {
        $data = [
            'total_amount' => 1000,
            'product_code' => 'EPAYTEST',
            'custom_field' => 'value',
        ];

        $result = $this->strategy->buildPaymentData($data);

        $this->assertEquals(1000, $result['total_amount']);
        $this->assertEquals('EPAYTEST', $result['product_code']);
        $this->assertEquals('value', $result['custom_field']);
    }

    public function test_build_payment_data_allows_url_override()
    {
        $data = [
            'total_amount' => 1000,
            'success_url' => 'https://custom.com/success',
        ];

        $result = $this->strategy->buildPaymentData($data);

        $this->assertEquals('https://custom.com/success', $result['success_url']);
    }

    public function test_extract_reference_id_returns_transaction_uuid()
    {
        $data = [
            'transaction_uuid' => 'txn-uuid-123',
            'total_amount' => 1000,
        ];

        $result = $this->strategy->extractReferenceId($data);

        $this->assertEquals('txn-uuid-123', $result);
    }

    public function test_extract_reference_id_returns_null_when_uuid_missing()
    {
        $data = [
            'total_amount' => 1000,
            'other_field' => 'value',
        ];

        $result = $this->strategy->extractReferenceId($data);

        $this->assertNull($result);
    }

    public function test_extract_reference_id_with_empty_data()
    {
        $data = [];

        $result = $this->strategy->extractReferenceId($data);

        $this->assertNull($result);
    }

    public function test_implements_strategy_interface()
    {
        $this->assertInstanceOf(
            \JaapTech\NepaliPayment\Strategies\PaymentInterceptorStrategy::class,
            $this->strategy
        );
    }
}
