<?php

declare(strict_types=1);

namespace JaapTech\NepaliPayment\Services;

use Illuminate\Contracts\Config\Repository;
use Kbk\NepaliPaymentGateway\Epay\ConnectIps;
use Kbk\NepaliPaymentGateway\Epay\Esewa;
use Kbk\NepaliPaymentGateway\Epay\Khalti;

class PaymentManager
{
    protected array $drivers = [];

    public function __construct(protected Repository $config) {}

    public function esewa(): Esewa
    {
        return $this->drivers['esewa'] ??= $this->createEsewa();
    }

    public function khalti(): Khalti
    {
        return $this->drivers['khalti'] ??= $this->createKhalti();
    }

    public function connectips(): ConnectIps
    {
        return $this->drivers['connectips'] ??= $this->createConnectIps();
    }

    protected function createEsewa(): Esewa
    {
        $config = $this->config->get('nepali-payment.esewa');

        $this->ensureConfig('esewa', ['product_code', 'secret_key']);

        return new Esewa(
            $config['product_code'],
            $config['secret_key']
        );
    }

    protected function createKhalti(): Khalti
    {
        $config = $this->config->get('nepali-payment.khalti');

        $this->ensureConfig('khalti', ['secret_key']);

        return new Khalti(
            $config['secret_key'],
            $config['environment']
        );
    }

    protected function createConnectIps(): ConnectIps
    {
        $config = $this->config->get('nepali-payment.connectips');

        $this->ensureConfig('connectips', [
            'merchant_id',
            'app_id',
            'app_name',
            'password',
            'private_key_path',
        ]);

        return new ConnectIps([
            'base_url' => $config['environment'] === 'test'
                ? 'https://uat.connectips.com'
                : 'https://connectips.com',
            ...$config,
        ]);
    }

    protected function ensureConfig(string $gateway, array $keys): void
    {
        foreach ($keys as $key) {
            if (empty($this->config->get("nepali-payment.{$gateway}.{$key}"))) {
                throw new \RuntimeException(
                    "Missing config for nepali-payment [{$gateway}.{$key}]"
                );
            }
        }
    }
}
