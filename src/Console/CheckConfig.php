<?php

declare(strict_types=1);

namespace JaapTech\NepaliPayment\Console;

use Illuminate\Console\Command;

class CheckConfig extends Command
{
    protected $signature = 'nepali-payment:check';

    protected $description = 'Check Nepali Payment package configuration and credentials';

    public function handle()
    {
        $gateways = ['esewa', 'khalti', 'connectips'];

        $this->info('Checking Nepali Payment Gateway Configuration:');

        foreach ($gateways as $gateway) {
            $config = config("nepali-payment.$gateway");

            if (! $config) {
                $this->error("- $gateway: config not found");

                continue;
            }

            // List required keys per gateway
            $requiredKeys = match ($gateway) {
                'esewa' => ['product_code', 'secret_key'],
                'khalti' => ['secret_key', 'environment'],
                'connectips' => ['merchant_id', 'app_id', 'app_name', 'password', 'private_key_path', 'environment'],
            };

            $missingKeys = [];
            foreach ($requiredKeys as $key) {
                if (empty($config[$key])) {
                    $missingKeys[] = $key;
                }
            }

            if ($missingKeys) {
                $this->error("- $gateway: missing keys: ".implode(', ', $missingKeys));
            } else {
                $this->info("- $gateway: ✅ configured");
            }
        }

        return 0;
    }
}
