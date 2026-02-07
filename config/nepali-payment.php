<?php

return [
    'esewa' => [
        'product_code' => env('ESEWA_PRODUCT_CODE'),
        'secret_key' => env('ESEWA_SECRET_KEY'),
        'success_url' => env('ESEWA_SUCCESS_URL'),
        'failure_url' => env('ESEWA_FAILURE_URL'),
    ],
    'khalti' => [
        'secret_key' => env('KHALTI_SECRET_KEY'),
        'environment' => strtolower(env('KHALTI_ENVIRONMENT', 'test')),
        'success_url' => strtolower(env('KHALTI_SUCCESS_URL')),
        'website_url' => strtolower(env('KHALTI_WEBSITE_URL')),
    ],
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
    | Set true to enable database logging of payments. Default value is false
    |
    */
    'database' => [
        'enabled' => env('NEPALI_PAYMENT_DATABASE_ENABLED', false),
    ],
];
