<?php

declare(strict_types=1);

namespace JaapTech\NepaliPayment\Factories;

use Illuminate\Contracts\Config\Repository;
use JaapTech\NepaliPayment\Enums\NepaliPaymentGateway;
use Kbk\NepaliPaymentGateway\Contracts\BasePaymentGateway;
use Kbk\NepaliPaymentGateway\Epay\ConnectIps;
use Kbk\NepaliPaymentGateway\Epay\Esewa;
use Kbk\NepaliPaymentGateway\Epay\Khalti;
use Kbk\NepaliPaymentGateway\Exceptions\InvalidPayloadException;
use RuntimeException;

/**
 * GatewayFactory - Creates and caches gateway instances
 * Responsible for instantiating payment gateways with proper configuration
 * and caching them to avoid redundant instantiation.
 */
class GatewayFactory
{
    private array $drivers = [];

    public function __construct(private readonly Repository $config) {}

    /**
     * Create or retrieve a cached gateway instance by name.
     *
     * @throws RuntimeException|InvalidPayloadException If gateway is not supported or config is missing
     */
    public function make(string|NepaliPaymentGateway $gateway): BasePaymentGateway
    {
        // Convert enum to string if needed
        $gatewayName = $gateway instanceof NepaliPaymentGateway ? $gateway->value : $gateway;

        $this->validateGateway($gatewayName);

        // Return cached instance or create new one
        return $this->drivers[$gatewayName] ??= $this->create($gatewayName);
    }

    /**
     * Create a new gateway instance based on the gateway name.
     *
     * @throws RuntimeException|InvalidPayloadException If configuration is missing
     */
    private function create(string $gatewayName): object
    {
        return match ($gatewayName) {
            NepaliPaymentGateway::ESEWA->value => $this->createEsewa(),
            NepaliPaymentGateway::KHALTI->value => $this->createKhalti(),
            NepaliPaymentGateway::CONNECTIPS->value => $this->createConnectIps(),
            default => throw new RuntimeException("Unsupported gateway: {$gatewayName}"),
        };
    }

    /**
     * Validate that a gateway name is supported.
     *
     * @throws RuntimeException If gateway is not supported
     */
    private function validateGateway(string $gatewayName): void
    {
        $supportedGateways = array_map(
            fn (NepaliPaymentGateway $gateway) => $gateway->value,
            NepaliPaymentGateway::cases(),
        );

        if (! in_array($gatewayName, $supportedGateways)) {
            throw new RuntimeException(
                "Unsupported gateway: {$gatewayName}. Supported gateways: ".implode(', ', $supportedGateways)
            );
        }
    }

    /**
     * Create eSewa gateway instance.
     *
     * @throws InvalidPayloadException
     */
    private function createEsewa(): Esewa
    {
        $config = $this->config->get('nepali-payment.esewa');
        $this->ensureConfig('esewa', ['product_code', 'secret_key']);

        return new Esewa($config['product_code'], $config['secret_key']);
    }

    /**
     * Create Khalti gateway instance.
     *
     * @throws InvalidPayloadException
     */
    private function createKhalti(): Khalti
    {
        $config = $this->config->get('nepali-payment.khalti');
        $this->ensureConfig('khalti', ['secret_key']);

        return new Khalti($config['secret_key'], $config['environment']);
    }

    /**
     * Create ConnectIps gateway instance.
     *
     * @throws InvalidPayloadException
     */
    private function createConnectIps(): ConnectIps
    {
        $config = $this->config->get('nepali-payment.connectips');

        $this->ensureConfig('connectips', [
            'merchant_id',
            'app_id',
            'app_name',
            'password',
            'private_key_path',
        ]);

        $baseUrl = $config['environment'] === 'test' ? 'https://uat.connectips.com' : 'https://connectips.com';

        return new ConnectIps([
            'base_url' => $baseUrl,
            ...$config,
        ]);
    }

    /**
     * Ensure required configuration keys are set.
     *
     * @throws RuntimeException If required config is missing
     */
    private function ensureConfig(string $gateway, array $keys): void
    {
        foreach ($keys as $key) {
            if (empty($this->config->get("nepali-payment.{$gateway}.{$key}"))) {
                throw new RuntimeException("Missing config for nepali-payment [{$gateway}.{$key}]");
            }
        }
    }

    /**
     * Check if a gateway is cached.
     */
    public function isCached(string|NepaliPaymentGateway $gateway): bool
    {
        $gatewayName = $gateway instanceof NepaliPaymentGateway ? $gateway->value : $gateway;

        return isset($this->drivers[$gatewayName]);
    }

    /**
     * Clear a cached gateway instance.
     */
    public function forget(string|NepaliPaymentGateway $gateway): void
    {
        $gatewayName = $gateway instanceof NepaliPaymentGateway ? $gateway->value : $gateway;

        unset($this->drivers[$gatewayName]);
    }

    /**
     * Clear all cached gateway instances.
     */
    public function flush(): void
    {
        $this->drivers = [];
    }
}
