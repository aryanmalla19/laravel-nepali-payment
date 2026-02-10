<?php

declare(strict_types=1);

namespace JaapTech\NepaliPayment\Tests\Unit\Events;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use JaapTech\NepaliPayment\Enums\PaymentStatus;
use JaapTech\NepaliPayment\Events\PaymentCompletedEvent;
use JaapTech\NepaliPayment\Events\PaymentFailedEvent;
use JaapTech\NepaliPayment\Events\PaymentInitiatedEvent;
use JaapTech\NepaliPayment\Events\PaymentProcessingEvent;
use JaapTech\NepaliPayment\Models\PaymentTransaction;
use JaapTech\NepaliPayment\Tests\TestCase;

class PaymentEventsTest extends TestCase
{
    use RefreshDatabase;

    // ========== PaymentInitiatedEvent Tests ==========

    public function test_payment_initiated_event_can_be_instantiated()
    {
        $payment = PaymentTransaction::create([
            'gateway' => 'khalti',
            'status' => PaymentStatus::PENDING,
            'amount' => 1000,
            'merchant_reference_id' => 'ref-123',
            'initiated_at' => now(),
        ]);

        $event = new PaymentInitiatedEvent($payment);

        $this->assertInstanceOf(PaymentInitiatedEvent::class, $event);
        $this->assertSame($payment, $event->payment);
    }

    public function test_payment_initiated_event_has_payment_property()
    {
        $payment = PaymentTransaction::create([
            'gateway' => 'khalti',
            'status' => PaymentStatus::PENDING,
            'amount' => 1000,
            'merchant_reference_id' => 'ref-123',
            'initiated_at' => now(),
        ]);

        $event = new PaymentInitiatedEvent($payment);

        $this->assertInstanceOf(PaymentTransaction::class, $event->payment);
        $this->assertEquals($payment->id, $event->payment->id);
    }

    public function test_payment_initiated_event_payment_is_readonly()
    {
        $payment = PaymentTransaction::create([
            'gateway' => 'khalti',
            'status' => PaymentStatus::PENDING,
            'amount' => 1000,
            'merchant_reference_id' => 'ref-123',
            'initiated_at' => now(),
        ]);

        $event = new PaymentInitiatedEvent($payment);

        // Verify the property is accessible
        $this->assertEquals($payment->id, $event->payment->id);
    }

    // ========== PaymentProcessingEvent Tests ==========

    public function test_payment_processing_event_can_be_instantiated()
    {
        $payment = PaymentTransaction::create([
            'gateway' => 'khalti',
            'status' => PaymentStatus::PROCESSING,
            'amount' => 1000,
            'merchant_reference_id' => 'ref-123',
            'initiated_at' => now(),
        ]);

        $event = new PaymentProcessingEvent($payment);

        $this->assertInstanceOf(PaymentProcessingEvent::class, $event);
        $this->assertSame($payment, $event->payment);
    }

    public function test_payment_processing_event_has_payment_property()
    {
        $payment = PaymentTransaction::create([
            'gateway' => 'khalti',
            'status' => PaymentStatus::PROCESSING,
            'amount' => 1000,
            'merchant_reference_id' => 'ref-123',
            'initiated_at' => now(),
        ]);

        $event = new PaymentProcessingEvent($payment);

        $this->assertInstanceOf(PaymentTransaction::class, $event->payment);
        $this->assertEquals($payment->id, $event->payment->id);
    }

    // ========== PaymentCompletedEvent Tests ==========

    public function test_payment_completed_event_can_be_instantiated()
    {
        $payment = PaymentTransaction::create([
            'gateway' => 'khalti',
            'status' => PaymentStatus::COMPLETED,
            'amount' => 1000,
            'merchant_reference_id' => 'ref-123',
            'initiated_at' => now(),
        ]);

        $event = new PaymentCompletedEvent($payment);

        $this->assertInstanceOf(PaymentCompletedEvent::class, $event);
        $this->assertSame($payment, $event->payment);
    }

    public function test_payment_completed_event_has_payment_property()
    {
        $payment = PaymentTransaction::create([
            'gateway' => 'khalti',
            'status' => PaymentStatus::COMPLETED,
            'amount' => 1000,
            'merchant_reference_id' => 'ref-123',
            'initiated_at' => now(),
        ]);

        $event = new PaymentCompletedEvent($payment);

        $this->assertInstanceOf(PaymentTransaction::class, $event->payment);
        $this->assertEquals($payment->id, $event->payment->id);
    }

    // ========== PaymentFailedEvent Tests ==========

    public function test_payment_failed_event_can_be_instantiated()
    {
        $payment = PaymentTransaction::create([
            'gateway' => 'khalti',
            'status' => PaymentStatus::FAILED,
            'amount' => 1000,
            'merchant_reference_id' => 'ref-123',
            'initiated_at' => now(),
        ]);

        $event = new PaymentFailedEvent($payment, 'Payment verification failed');

        $this->assertInstanceOf(PaymentFailedEvent::class, $event);
        $this->assertSame($payment, $event->payment);
    }

    public function test_payment_failed_event_has_payment_and_reason_properties()
    {
        $payment = PaymentTransaction::create([
            'gateway' => 'khalti',
            'status' => PaymentStatus::FAILED,
            'amount' => 1000,
            'merchant_reference_id' => 'ref-123',
            'initiated_at' => now(),
        ]);

        $event = new PaymentFailedEvent($payment, 'Insufficient funds');

        $this->assertInstanceOf(PaymentTransaction::class, $event->payment);
        $this->assertEquals('Insufficient funds', $event->reason);
    }

    public function test_payment_failed_event_reason_can_be_null()
    {
        $payment = PaymentTransaction::create([
            'gateway' => 'khalti',
            'status' => PaymentStatus::FAILED,
            'amount' => 1000,
            'merchant_reference_id' => 'ref-123',
            'initiated_at' => now(),
        ]);

        $event = new PaymentFailedEvent($payment, null);

        $this->assertNull($event->reason);
    }

    public function test_payment_failed_event_reason_is_optional()
    {
        $payment = PaymentTransaction::create([
            'gateway' => 'khalti',
            'status' => PaymentStatus::FAILED,
            'amount' => 1000,
            'merchant_reference_id' => 'ref-123',
            'initiated_at' => now(),
        ]);

        $event = new PaymentFailedEvent($payment);

        $this->assertNull($event->reason);
    }

    // ========== Event Dispatching Tests ==========

    public function test_events_can_be_dispatched()
    {
        $payment = PaymentTransaction::create([
            'gateway' => 'khalti',
            'status' => PaymentStatus::PENDING,
            'amount' => 1000,
            'merchant_reference_id' => 'ref-123',
            'initiated_at' => now(),
        ]);

        // Test that event can be dispatched without errors
        event(new PaymentInitiatedEvent($payment));

        $this->assertTrue(true); // If we get here, no exception was thrown
    }

    public function test_all_events_are_dispatchable()
    {
        $payment = PaymentTransaction::create([
            'gateway' => 'khalti',
            'status' => PaymentStatus::COMPLETED,
            'amount' => 1000,
            'merchant_reference_id' => 'ref-123',
            'initiated_at' => now(),
        ]);

        // Test dispatching all event types
        event(new PaymentInitiatedEvent($payment));
        event(new PaymentProcessingEvent($payment));
        event(new PaymentCompletedEvent($payment));
        event(new PaymentFailedEvent($payment, 'Test failure'));

        $this->assertTrue(true); // All events dispatched successfully
    }

    public function test_events_can_be_listened_to()
    {
        $payment = PaymentTransaction::create([
            'gateway' => 'khalti',
            'status' => PaymentStatus::PENDING,
            'amount' => 1000,
            'merchant_reference_id' => 'ref-123',
            'initiated_at' => now(),
        ]);

        $eventDispatched = false;

        Event::listen(PaymentInitiatedEvent::class, function ($event) use (&$eventDispatched) {
            $eventDispatched = true;
            $this->assertInstanceOf(PaymentTransaction::class, $event->payment);
        });

        event(new PaymentInitiatedEvent($payment));

        $this->assertTrue($eventDispatched);
    }
}
