<?php

return [

    /*
    |--------------------------------------------------------------------------
    | eSewa Payment Gateway Configuration
    |--------------------------------------------------------------------------
    |
    | To use eSewa for payments, you need to register as a merchant and obtain your
    | credentials from the eSewa merchant portal.
    |
    | Product Code: Your unique merchant product code provided by eSewa
    | Secret Key: Your merchant secret key for API authentication
    | Success URL: URL where customers are redirected after successful payment
    | Failure URL: URL where customers are redirected after failed/cancelled payment
    |
    */
    'esewa' => [
        'product_code' => env('ESEWA_PRODUCT_CODE'),
        'secret_key' => env('ESEWA_SECRET_KEY'),
        'success_url' => env('ESEWA_SUCCESS_URL'),
        'failure_url' => env('ESEWA_FAILURE_URL'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Khalti Payment Gateway Configuration
    |--------------------------------------------------------------------------
    |
    | Khalti is another major payment gateway in Nepal supporting digital
    | wallets, internet banking, and cards. Register at Khalti merchant
    | portal to get your API credentials.
    |
    | Secret Key: Your Khalti API secret key for authentication
    | Environment: 'test' for sandbox/testing, 'live' for production
    | Success URL: URL where customers return after successful payment
    | Website URL: Your website's main URL (required by Khalti)
    |
    */
    'khalti' => [
        'secret_key' => env('KHALTI_SECRET_KEY'),
        'environment' => strtolower(env('KHALTI_ENVIRONMENT', 'test')),
        'success_url' => strtolower(env('KHALTI_SUCCESS_URL')),
        'website_url' => strtolower(env('KHALTI_WEBSITE_URL')),
    ],

    /*
    |--------------------------------------------------------------------------
    | ConnectIPS Payment Gateway Configuration
    |--------------------------------------------------------------------------
    |
    | ConnectIPS is Nepal's national payment gateway connecting banks and
    | wallets. It requires additional security credentials including
    | certificate-based authentication.
    |
    | Merchant ID: Your unique ConnectIPS merchant identifier
    | App ID: Your application ID provided by ConnectIPS
    | App Name: Your application's registered name
    | Private Key Path: Absolute path to your private key file (.pem)
    | Password: Password for your private key file
    | Environment: 'test' for UAT/testing, 'live' for production
    |
    */
    'connectips' => [
        'merchant_id' => env('CONNECTIPS_MERCHANT_ID'),
        'app_id' => env('CONNECTIPS_APP_ID'),
        'app_name' => env('CONNECTIPS_APP_NAME'),
        'private_key_path' => env('CONNECTIPS_PRIVATE_KEY_PATH'),
        'password' => env('CONNECTIPS_PASSWORD'),
        'environment' => strtolower(env('CONNECTIPS_ENVIRONMENT', 'test')),
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Configuration
    |--------------------------------------------------------------------------
    |
    | Configure database integration for tracking and managing payments.
    | When enabled, all payment transactions will be automatically logged
    | to your database for record keeping, reconciliation, and analytics.
    |
    | Enabled: Set to true to enable automatic payment logging
    |          Set to false to disable (payments will still process)
    |
    */
    'database' => [
        'enabled' => env('NEPALI_PAYMENT_DATABASE_ENABLED', false),
    ],
];
