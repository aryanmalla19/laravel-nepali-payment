# Laravel Nepali Payment - Complete Feature Set

## Core Features (Unchanged - Backward Compatible)

### Payment Gateway Support
- ✅ eSewa integration
- ✅ Khalti integration
- ✅ ConnectIps integration

### Facade API
```php
NepaliPayment::esewa()      // Get eSewa gateway instance
NepaliPayment::khalti()     // Get Khalti gateway instance
NepaliPayment::connectips() // Get ConnectIps gateway instance
```

### Artisan Commands
```bash
php artisan nepali-payment:check  # Validate gateway configuration
```

---

## New Features (v0.1.0 - Database Integration)

### 1. Database Models

#### Payment Model (`JaapTech\NepaliPayment\Models\Payment`)

**Relationships:**
- `payable()` - Polymorphic relation to any model (User, Order, Invoice, etc.)
- `refunds()` - HasMany relation to PaymentRefund

**Query Scopes:**
```php
Payment::byGateway('esewa')              // Filter by gateway
Payment::byStatus(PaymentStatus::COMPLETED) // Filter by status
Payment::completed()                      // Shortcut for completed
Payment::failed()                         // Shortcut for failed
Payment::pending()                        // Shortcut for pending/processing
Payment::byReference('ref-123')          // Find by reference ID
Payment::byGatewayTransactionId('txn-456') // Find by gateway txn ID
Payment::forPayable('App\Models\User', $userId) // Filter by payable model
```

**Methods:**
```php
$payment->isCompleted()           // Check if completed
$payment->isFailed()              // Check if failed
$payment->isPending()             // Check if pending/processing
$payment->canBeRefunded()         // Check if refundable
$payment->markAsProcessing()      // Transition to processing
$payment->markAsCompleted()       // Transition to completed
$payment->markAsFailed($reason)   // Transition to failed
$payment->markAsCancelled()       // Transition to cancelled
$payment->markAsRefunded()        // Transition to refunded

// Refund tracking
$payment->getTotalRefundedAmount()      // Sum of completed refunds
$payment->getRemainingRefundableAmount() // Amount still refundable
$payment->hasRefunds()                  // Check if any refunds exist
```

#### PaymentRefund Model (`JaapTech\NepaliPayment\Models\PaymentRefund`)

**Relationships:**
- `payment()` - BelongsTo Payment model

**Query Scopes:**
```php
PaymentRefund::completed()  // Completed refunds
PaymentRefund::pending()    // Pending refunds
PaymentRefund::failed()     // Failed refunds
PaymentRefund::processing() // Processing refunds
```

**Methods:**
```php
$refund->isCompleted()           // Check if completed
$refund->isPending()             // Check if pending
$refund->isFailed()              // Check if failed
$refund->markAsProcessing()      // Start processing
$refund->markAsCompleted($id)    // Complete refund
$refund->markAsFailed($reason)   // Fail refund
```

---

### 2. Enums

#### PaymentStatus Enum
```php
PaymentStatus::PENDING       // Awaiting payment
PaymentStatus::PROCESSING    // Verified, awaiting confirmation
PaymentStatus::COMPLETED     // Successfully completed ✓
PaymentStatus::FAILED        // Failed
PaymentStatus::REFUNDED      // Refunded
PaymentStatus::CANCELLED     // Cancelled

// Helper methods
$status->isTerminal()        // Is it a terminal state?
$status->isPending()         // Is it pending or processing?
$status->label()             // Human-readable label
$status->color()             // UI color for display
```

#### RefundReason Enum
```php
RefundReason::USER_REQUEST   // Customer requested refund
RefundReason::DUPLICATE      // Payment was duplicated
RefundReason::ERROR          // System error occurred
RefundReason::OTHER          // Other reason

// Helper methods
$reason->label()             // Human-readable label
$reason->description()       // Detailed description
```

#### RefundStatus Enum
```php
RefundStatus::PENDING        // Refund requested
RefundStatus::PROCESSING     // Processing refund
RefundStatus::COMPLETED      // Refund successful ✓
RefundStatus::FAILED         // Refund failed

// Helper methods
$status->isTerminal()        // Is it terminal?
$status->label()             // Human-readable label
$status->color()             // UI color for display
```

---

### 3. Payment Manager Extensions

**15 New Methods in PaymentManager:**

**Create Operations:**
```php
NepaliPayment::createPayment(
    gateway: 'esewa',
    amount: 1000,
    paymentData: [],
    payableType: 'App\Models\User',
    payableId: auth()->id(),
    metadata: []
) // Returns: Payment model

NepaliPayment::createRefund(
    payment: $payment,
    refundAmount: 500,
    reason: RefundReason::USER_REQUEST,
    notes: 'Optional notes',
    requestedBy: auth()->id()
) // Returns: PaymentRefund model
```

**Record Operations:**
```php
NepaliPayment::recordPaymentVerification(
    payment: $payment,
    verificationData: $data,
    isSuccess: true
) // Void - updates payment status

NepaliPayment::processRefund(
    refund: $refund,
    responseData: $data,
    isSuccess: true
) // Void - updates refund status
```

**Status Operations:**
```php
NepaliPayment::completePayment(
    payment: $payment,
    gatewayTransactionId: 'txn-123',
    responseData: []
) // Void - marks as completed

NepaliPayment::failPayment(
    payment: $payment,
    reason: 'Optional reason'
) // Void - marks as failed
```

**Query Operations:**
```php
NepaliPayment::findPaymentByReference('ref-123')
// Returns: Payment|null

NepaliPayment::findPaymentByGatewayId('txn-456')
// Returns: Payment|null

NepaliPayment::getPaymentsByStatus(PaymentStatus::COMPLETED)
// Returns: Eloquent\Builder for chaining

NepaliPayment::getPaymentsByGateway('khalti')
// Returns: Eloquent\Builder for chaining
```

---

### 4. Events System

**5 Event Classes:**

1. **PaymentInitiatedEvent**
   - Fired when: Payment record created
   - Contains: `$event->payment`

2. **PaymentProcessingEvent**
   - Fired when: Payment verified by gateway
   - Contains: `$event->payment`

3. **PaymentCompletedEvent**
   - Fired when: Payment confirmed as successful
   - Contains: `$event->payment`

4. **PaymentFailedEvent**
   - Fired when: Payment fails
   - Contains: `$event->payment`, `$event->reason`

5. **PaymentRefundedEvent**
   - Fired when: Refund is completed
   - Contains: `$event->refund`

**Usage Example:**
```php
// In EventServiceProvider
protected $listen = [
    PaymentCompletedEvent::class => [
        SendPaymentConfirmationEmail::class,
        UpdateUserBalanceListener::class,
    ],
    PaymentFailedEvent::class => [
        NotifyPaymentFailureListener::class,
    ],
];

// Or inline
Event::listen(PaymentCompletedEvent::class, function ($event) {
    $payment = $event->payment;
    // Handle completed payment
});
```

---

### 5. Helper Functions

7 convenient helper functions for common operations:

```php
// Check database integration status
nepali_payment_enabled() // Returns: bool

// Find payments
nepali_payment_find('ref-123')                    // Returns: Payment|null
nepali_payment_find_by_gateway_id('txn-456')      // Returns: Payment|null

// Create payment
nepali_payment_create(
    gateway: 'khalti',
    amount: 2000,
    paymentData: [],
    payableType: 'App\Models\Order',
    payableId: $orderId,
    metadata: ['notes' => 'value']
) // Returns: Payment

// Create refund
nepali_payment_refund(
    $payment,
    100,
    'user_request',
    'Customer notes'
) // Returns: PaymentRefund

// Query helpers
nepali_payment_get_by_status('completed')  // Returns: Eloquent\Builder
nepali_payment_get_by_gateway('esewa')     // Returns: Eloquent\Builder
```

---

### 6. Enhanced Facade

All new methods available through facade:

```php
use NepaliPayment;

// Original methods (unchanged)
NepaliPayment::esewa()
NepaliPayment::khalti()
NepaliPayment::connectips()

// New database methods
NepaliPayment::createPayment(...)
NepaliPayment::recordPaymentVerification(...)
NepaliPayment::completePayment(...)
NepaliPayment::failPayment(...)
NepaliPayment::createRefund(...)
NepaliPayment::processRefund(...)
NepaliPayment::findPaymentByReference(...)
NepaliPayment::findPaymentByGatewayId(...)
NepaliPayment::getPaymentsByStatus(...)
NepaliPayment::getPaymentsByGateway(...)
```

---

### 7. Database Schema

**payments table:**
- id (UUID primary key)
- gateway (enum: esewa, khalti, connectips)
- status (enum: pending, processing, completed, failed, refunded, cancelled)
- payable_type & payable_id (polymorphic)
- amount (decimal)
- currency (default: NPR)
- reference_id (unique)
- gateway_transaction_id (unique, nullable)
- gateway_response (json)
- description (text, nullable)
- metadata (json, nullable)
- initiated_at, verified_at, completed_at, failed_at, refunded_at (timestamps)
- created_at, updated_at (timestamps)

**payment_refunds table:**
- id (UUID primary key)
- payment_id (FK, cascade delete)
- refund_amount (decimal)
- refund_reason (enum)
- refund_status (enum)
- gateway_refund_id (nullable)
- gateway_response (json, nullable)
- notes (text, nullable)
- requested_by & processed_by (user IDs, nullable)
- requested_at & processed_at (timestamps)
- created_at, updated_at (timestamps)

---

### 8. Configuration Options

```php
// config/nepali-payment.php
'database' => [
    'enabled' => env('NEPALI_PAYMENT_DATABASE_ENABLED', false),
]
```

**Environment Variables:**
```
NEPALI_PAYMENT_DATABASE_ENABLED=true  # Enable/disable database integration
```

## Use Cases

### 1. Simple Payment Tracking
Track when and how much users paid, but don't need refunds.

### 2. E-Commerce Orders
Attach payments to orders, track payment history per order.

### 3. SaaS Subscriptions
Attach payments to subscription records, manage recurring payments.

### 4. Invoice Payments
Track which invoices are paid, partially paid, or pending.

### 5. Donation Platform
Anonymous payments (no payable model) with optional user association.

### 6. Multi-Merchant System
Use metadata to track which merchant received the payment.

---

## Scalability Features

✅ **Database-backed** - Handle thousands of payments
✅ **Indexed queries** - Fast lookups by status, gateway, reference
✅ **Event-driven** - Extensible without modifying code
✅ **Polymorphic** - Works with any model in your app
✅ **Flexible refunds** - Supports partial and multiple refunds
✅ **Audit trail** - Complete payment and refund history
✅ **JSON storage** - Store any gateway response for debugging
✅ **Metadata** - Attach custom data to payments
✅ **UUID/Ulid support** - Modern primary key options

---

## Installation & Setup

```bash
# 1. Install
composer require jaap-tech/laravel-nepali-payment

# 2. Publish config
php artisan vendor:publish --tag=nepali-payment-config

# 3. Enable database (optional)
# Set NEPALI_PAYMENT_DATABASE_ENABLED=true in .env

# 4. Publish migrations
php artisan vendor:publish --tag=nepali-payment-migrations

# 5. Run migrations
php artisan migrate

# 6. Set credentials
# Add payment gateway environment variables

# 7. Verify
php artisan nepali-payment:check
```

---

## Documentation

See included documentation:
- **README.md** - Complete user guide with examples
- **IMPLEMENTATION_SUMMARY.md** - Technical implementation details
- **FEATURES.md** - This file

---
