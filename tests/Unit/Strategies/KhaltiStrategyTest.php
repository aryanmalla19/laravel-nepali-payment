<?php

declare(strict_types=1);

namespace JaapTech\NepaliPayment\Tests\Unit\Strategies;

use Illuminate\Config\Repository;
use JaapTech\NepaliPayment\Strategies\KhaltiStrategy;
use PHPUnit\Framework\TestCase;

class KhaltiStrategyTest extends TestCase
{
    private Repository $config;
    private KhaltiStrategy $strategy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->config = new Repository([
            'nepali-payment' => [
                'khalti' => [
                    'success_url' => 'https://example.com/success',
                    'website_url' => 'https://example.com',
                ],
            ],
        ]);

        $this->strategy = new KhaltiStrategy($this->config);
    }

    public function test_build_payment_data_merges_config_urls()
    {
        $data = [
            'amount' => 1000,
            'purchase_order_id' => 'order-123',
        ];

        $result = $this->strategy->buildPaymentData($data);

        $this->assertEquals('https://example.com/success', $result['success_url']);
        $this->assertEquals('https://example.com', $result['website_url']);
        $this->assertEquals(1000, $result['amount']);
        $this->assertEquals('order-123', $result['purchase_order_id']);
    }

    public function test_build_payment_data_preserves_original_data()
    {
        $data = [
            'amount' => 1000,
            'custom_field' => 'custom_value',
            'nested' => ['key' => 'value'],
        ];

        $result = $this->strategy->buildPaymentData($data);

        $this->assertEquals(1000, $result['amount']);
        $this->assertEquals('custom_value', $result['custom_field']);
        $this->assertEquals(['key' => 'value'], $result['nested']);
    }

    public function test_build_payment_data_allows_url_override()
    {
        $data = [
            'amount' => 1000,
            'success_url' => 'https://custom.com/success',
        ];

        $result = $this->strategy->buildPaymentData($data);

        // Original data should take precedence (array_merge behavior)
        $this->assertEquals('https://custom.com/success', $result['success_url']);
    }

    public function test_extract_reference_id_returns_pidx()
    {
        $data = [
            'pidx' => 'pidx-123-abc',
            'amount' => 1000,
        ];

        $result = $this->strategy->extractReferenceId($data);

        $this->assertEquals('pidx-123-abc', $result);
    }

    public function test_extract_reference_id_returns_null_when_pidx_missing()
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
