<?php

declare(strict_types=1);

namespace JaapTech\NepaliPayment\Tests\Unit\Strategies;

use Illuminate\Config\Repository;
use JaapTech\NepaliPayment\Strategies\ConnectIpsStrategy;
use PHPUnit\Framework\TestCase;

class ConnectIpsStrategyTest extends TestCase
{
    private Repository $config;
    private ConnectIpsStrategy $strategy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->config = new Repository([
            'nepali-payment' => [
                'connectips' => [
                    'return_url' => 'https://example.com/return',
                ],
            ],
        ]);

        $this->strategy = new ConnectIpsStrategy($this->config);
    }

    public function test_build_payment_data_merges_config_url()
    {
        $data = [
            'amount' => 1000,
            'transaction_id' => 'txn-123',
        ];

        $result = $this->strategy->buildPaymentData($data);

        $this->assertEquals('https://example.com/return', $result['return_url']);
        $this->assertEquals(1000, $result['amount']);
        $this->assertEquals('txn-123', $result['transaction_id']);
    }

    public function test_build_payment_data_preserves_original_data()
    {
        $data = [
            'amount' => 1000,
            'merchant_id' => 'MERCHANT123',
            'custom_field' => 'value',
        ];

        $result = $this->strategy->buildPaymentData($data);

        $this->assertEquals(1000, $result['amount']);
        $this->assertEquals('MERCHANT123', $result['merchant_id']);
        $this->assertEquals('value', $result['custom_field']);
    }

    public function test_build_payment_data_allows_url_override()
    {
        $data = [
            'amount' => 1000,
            'return_url' => 'https://custom.com/return',
        ];

        $result = $this->strategy->buildPaymentData($data);

        $this->assertEquals('https://custom.com/return', $result['return_url']);
    }

    public function test_extract_reference_id_returns_transaction_uuid()
    {
        $data = [
            'transaction_uuid' => 'txn-uuid-123',
            'amount' => 1000,
        ];

        $result = $this->strategy->extractReferenceId($data);

        $this->assertEquals('txn-uuid-123', $result);
    }

    public function test_extract_reference_id_returns_txn_id_when_uuid_missing()
    {
        $data = [
            'txn_id' => 'txn-id-456',
            'amount' => 1000,
        ];

        $result = $this->strategy->extractReferenceId($data);

        $this->assertEquals('txn-id-456', $result);
    }

    public function test_extract_reference_id_prefers_transaction_uuid_over_txn_id()
    {
        $data = [
            'transaction_uuid' => 'uuid-123',
            'txn_id' => 'id-456',
            'amount' => 1000,
        ];

        $result = $this->strategy->extractReferenceId($data);

        $this->assertEquals('uuid-123', $result);
    }

    public function test_extract_reference_id_returns_null_when_both_missing()
    {
        $data = [
            'amount' => 1000,
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
