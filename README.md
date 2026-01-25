# Laravel Nepali Payment Gateway

A comprehensive Laravel package for integrating Nepali payment gateways (eSewa, Khalti, and ConnectIps) with optional database tracking and payment management.

## Features

✅ **Multiple Gateway Support**
- eSewa integration
- Khalti integration  
- ConnectIps integration

✅ **Database Integration (Optional)**
- Track payment history
- Store gateway responses
- Manage refunds
- Polymorphic payment associations
- UUID support

✅ **Event System**
- Payment lifecycle events
- Custom event listeners

✅ **Query Scopes & Helpers**
- Easy payment retrieval
- Status filtering
- Gateway filtering

## Installation

Install the package via Composer:

```bash
composer require jaap-tech/laravel-nepali-payment
```

The package will auto-register the service provider.

## Configuration

Publish the configuration file:

```bash
php artisan vendor:publish --tag=nepali-payment-config
```

This creates `config/nepali-payment.php` with the following structure:

```php
return [
    'esewa' => [
        'product_code' => env('ESEWA_PRODUCT_CODE'),
        'secret_key'   => env('ESEWA_SECRET_KEY'),
    ],
    'khalti' => [
        'secret_key' => env('KHALTI_SECRET_KEY'),
        'environment' => strtolower(env('KHALTI_ENVIRONMENT', 'test')),
    ],
    'connectips' => [
        'merchant_id' => env('CONNECTIPS_MERCHANT_ID'),
        'app_id' => env('CONNECTIPS_APP_ID'),
        'app_name' => env('CONNECTIPS_APP_NAME'),
        'private_key_path' => env('CONNECTIPS_PRIVATE_KEY_PATH'),
        'password' => env('CONNECTIPS_PASSWORD'),
        'environment' => strtolower(env('CONNECTIPS_ENVIRONMENT', 'test')),
    ],
    'database' => [
        'enabled' => env('NEPALI_PAYMENT_DATABASE_ENABLED', false),
    ],
];
```

### Environment Variables

Add these to your `.env` file:

**eSewa:**
```
ESEWA_PRODUCT_CODE=your_product_code
ESEWA_SECRET_KEY=your_secret_key
```

**Khalti:**
```
KHALTI_SECRET_KEY=your_secret_key
KHALTI_ENVIRONMENT=test  # or 'live'
```

**ConnectIps:**
```
CONNECTIPS_MERCHANT_ID=your_merchant_id
CONNECTIPS_APP_ID=your_app_id
CONNECTIPS_APP_NAME=your_app_name
CONNECTIPS_PRIVATE_KEY_PATH=/path/to/private/key
CONNECTIPS_PASSWORD=your_password
CONNECTIPS_ENVIRONMENT=test  # or 'live'
```

**Database Integration:**
```
NEPALI_PAYMENT_DATABASE_ENABLED=true
```

## Quick Start

### Basic Payment Flow (Without Database)

```php
use NepaliPayment;

// Initiate eSewa payment
$response = NepaliPayment::esewa()->payment([
    'amount' => 1000,
    'transaction_uuid' => 'unique-id-123',
    'success_url' => route('payment.success'),
    'failure_url' => route('payment.failure'),
]);

// Redirect to payment gateway
$response->redirect();
```

### With Database Integration

First, enable the database integration:

```bash
# 1. Publish migrations
php artisan vendor:publish --tag=nepali-payment-migrations

# 2. Run migrations
php artisan migrate

# 3. Enable in .env
NEPALI_PAYMENT_DATABASE_ENABLED=true
```

Now you can track payments:

```php
use NepaliPayment;

// Create a payment record
$payment = NepaliPayment::createPayment(
    gateway: 'esewa',
    amount: 1000,
    paymentData: [
        'reference_id' => 'ref-123',
        'description' => 'Product purchase',
    ],
    payableType: 'App\Models\User',
    payableId: auth()->id(),
    metadata: ['product_id' => 1]
);

// Initiate payment with gateway
$response = NepaliPayment::esewa()->payment([
    'amount' => 1000,
    'transaction_uuid' => $payment->reference_id,
    'success_url' => route('payment.success'),
    'failure_url' => route('payment.failure'),
]);

// Record verification
$verification = NepaliPayment::esewa()->verify([
    'product_code' => 'EPAYTEST',
    'total_amount' => 1000,
    'transaction_uuid' => $payment->reference_id,
]);

if ($verification->isSuccess()) {
    NepaliPayment::completePayment(
        $payment,
        gatewayTransactionId: $verification->getTransactionId(),
        responseData: $verification->toArray()
    );
}
```

## Database Models

### Payment Model

The `Payment` model stores all payment records with full lifecycle tracking.

**Scopes:**

```php
// Find payment by reference ID
Payment::byReference('ref-123')->first();

// Find by gateway transaction ID
Payment::byGatewayTransactionId('txn-456')->first();

// Filter by gateway
Payment::byGateway('esewa')->get();

// Filter by status
Payment::byStatus('completed')->get();

// Completed payments only
Payment::completed()->get();

// Failed payments only
Payment::failed()->get();

// Pending/processing payments
Payment::pending()->get();

// Filter by payable type and ID
Payment::forPayable('App\Models\User', $userId)->get();
```

**Methods:**

```php
$payment = Payment::find($id);

// Check status
$payment->isCompleted();
$payment->isFailed();
$payment->isPending();
$payment->canBeRefunded();

// Update status
$payment->markAsProcessing();
$payment->markAsCompleted();
$payment->markAsFailed('Reason for failure');
$payment->markAsCancelled();
$payment->markAsRefunded();

// Refund information
$payment->getTotalRefundedAmount();
$payment->getRemainingRefundableAmount();
$payment->hasRefunds();

// Relationships
$payment->payable;        // The associated model (User, Order, etc)
$payment->refunds();      // All refunds for this payment
```

### PaymentRefund Model

Tracks refunds separately for full audit trail.

**Scopes:**

```php
PaymentRefund::completed()->get();
PaymentRefund::pending()->get();
PaymentRefund::failed()->get();
PaymentRefund::processing()->get();
```

**Methods:**

```php
$refund = PaymentRefund::find($id);

// Check status
$refund->isCompleted();
$refund->isPending();
$refund->isFailed();

// Update status
$refund->markAsProcessing();
$refund->markAsCompleted($gatewayRefundId = null);
$refund->markAsFailed($reason = null);

// Relationships
$refund->payment;  // The associated payment
```

## Payment Statuses

| Status | Meaning | Transitions |
|--------|---------|-------------|
| `pending` | Initial state, awaiting user payment | → processing, failed, cancelled |
| `processing` | Payment verified, awaiting final confirmation | → completed, failed |
| `completed` | Payment successful ✓ | → refunded |
| `failed` | Payment failed | (terminal) |
| `refunded` | Payment refunded | (terminal) |
| `cancelled` | User cancelled payment | (terminal) |

## Refund Reasons

```php
RefundReason::USER_REQUEST  // Customer requested refund
RefundReason::DUPLICATE     // Payment processed twice
RefundReason::ERROR         // System error
RefundReason::OTHER         // Other reason
```

## Payment Management

### Create a Refund

```php
use NepaliPayment;
use JaapTech\NepaliPayment\Enums\RefundReason;

$payment = Payment::find($paymentId);

$refund = NepaliPayment::createRefund(
    payment: $payment,
    refundAmount: 500, // Partial refund
    reason: RefundReason::USER_REQUEST,
    notes: 'Customer requested refund',
    requestedBy: auth()->id()
);
```

### Process Refund with Gateway

```php
// For Khalti (supports refund)
try {
    $response = NepaliPayment::khalti()->refund([
        'transaction_id' => $payment->gateway_transaction_id,
        'amount' => 500,
    ]);

    NepaliPayment::processRefund(
        $refund,
        $response->toArray(),
        isSuccess: true
    );
} catch (\Exception $e) {
    NepaliPayment::processRefund(
        $refund,
        ['error' => $e->getMessage()],
        isSuccess: false
    );
}
```

## Helper Functions

Quick helpers for common operations:

```php
// Check if database is enabled
nepali_payment_enabled();

// Find payments
nepali_payment_find('ref-123');                    // by reference
nepali_payment_find_by_gateway_id('txn-456');      // by gateway ID

// Create payment
nepali_payment_create(
    'esewa',
    1000,
    paymentData: ['description' => 'Order #123'],
    payableType: 'App\Models\User',
    payableId: auth()->id()
);

// Create refund
nepali_payment_refund(
    $payment,
    500,
    'user_request',
    'Customer request'
);

// Query payments
nepali_payment_get_by_status('completed')->paginate();
nepali_payment_get_by_gateway('khalti')->latest()->get();
```

## Events

The package dispatches events at key lifecycle points:

```php
use JaapTech\NepaliPayment\Events\{
    PaymentInitiatedEvent,
    PaymentProcessingEvent,
    PaymentCompletedEvent,
    PaymentFailedEvent,
    PaymentRefundedEvent,
};

// Listen to events in EventServiceProvider
protected $listen = [
    PaymentCompletedEvent::class => [
        \App\Listeners\SendPaymentConfirmation::class,
        \App\Listeners\UpdateUserBalance::class,
    ],
    PaymentFailedEvent::class => [
        \App\Listeners\NotifyPaymentFailure::class,
    ],
    PaymentRefundedEvent::class => [
        \App\Listeners\ProcessRefundNotification::class,
    ],
];

// Or listen inline
Event::listen(PaymentCompletedEvent::class, function ($event) {
    // $event->payment
});
```

## Verify Configuration

Check if all gateway configurations are valid:

```bash
php artisan nepali-payment:check
```

Output example:
```
Checking Nepali Payment Gateway Configuration:
- esewa: ✅ configured
- khalti: ✅ configured
- connectips: ⚠️  missing keys: environment
```

## Example: Complete Payment Flow

```php
// PaymentController.php
<?php

namespace App\Http\Controllers;

use App\Models\Order;
use NepaliPayment;
use JaapTech\NepaliPayment\Models\Payment;

class PaymentController extends Controller
{
    public function create(Order $order)
    {
        // Create payment record in database
        $payment = NepaliPayment::createPayment(
            gateway: 'esewa',
            amount: $order->total,
            paymentData: [
                'description' => "Order #{$order->id}",
            ],
            payableType: Order::class,
            payableId: $order->id,
            metadata: ['order_id' => $order->id]
        );

        // Initiate payment with gateway
        $response = NepaliPayment::esewa()->payment([
            'amount' => $order->total,
            'transaction_uuid' => $payment->reference_id,
            'success_url' => route('payment.verify', $payment->id),
            'failure_url' => route('payment.failed', $payment->id),
        ]);

        return $response->redirect();
    }

    public function verify(Payment $payment)
    {
        // Get gateway response
        $verification = NepaliPayment::esewa()->verify([
            'product_code' => config('nepali-payment.esewa.product_code'),
            'total_amount' => $payment->amount,
            'transaction_uuid' => $payment->reference_id,
        ]);

        if ($verification->isSuccess()) {
            NepaliPayment::completePayment(
                $payment,
                gatewayTransactionId: $payment->reference_id,
                responseData: $verification->toArray()
            );

            // Update order status
            $payment->payable->update(['status' => 'paid']);

            return redirect()->route('order.show', $payment->payable)
                ->with('success', 'Payment completed successfully!');
        } else {
            NepaliPayment::failPayment($payment, 'Gateway verification failed');
            
            return redirect()->route('payment.create', $payment->payable)
                ->with('error', 'Payment verification failed');
        }
    }

    public function failed(Payment $payment)
    {
        NepaliPayment::failPayment($payment, 'User cancelled payment');
        
        return redirect()->route('payment.create', $payment->payable)
            ->with('error', 'Payment was cancelled');
    }
}
```

## Troubleshooting

**"Database integration is not enabled"** error
- Make sure `NEPALI_PAYMENT_DATABASE_ENABLED=true` in `.env`
- Run migrations: `php artisan migrate`

**"Missing config for nepali-payment"** error
- Check all required environment variables are set in `.env`
- Run: `php artisan nepali-payment:check`

**Payment not being saved to database**
- Verify database is enabled in config
- Check database connection is working
- Ensure migrations have been run

## License

This package is licensed under the MIT License. See the LICENSE file for details.

## Support

For issues, questions, or contributions, visit the [GitHub repository](https://github.com/aryanmalla19/nepali-payment-gateway).

## Changelog

### v0.1.0 - Database Integration Release
- Added Payment and PaymentRefund models with full relationship support
- Implemented PaymentStatus, RefundReason, and RefundStatus enums
- Created PaymentManager database methods for tracking payment lifecycle
- Added event system for payment state changes
- Published migrations for easy database setup
- Added helper functions for common operations
- Added comprehensive query scopes for filtering payments
- Support for polymorphic payment associations
- UUID/] configuration for primary keys
